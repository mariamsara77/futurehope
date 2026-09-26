export const API_BASE_URL = (process.env.NEXT_PUBLIC_API_BASE_URL || "https://futurehope.totthobox.com").replace(/\/$/, "");

const TOKEN_KEY = "futurehope_access_token";

type ApiResponse<T = unknown> = {
  data?: T;
  user?: T;
  profile?: T;
  access_token?: string;
  token?: string;
  message?: string;
  errors?: Record<string, string[] | string>;
};

export type AuthUser = {
  id: number | string;
  name?: string | null;
  email: string;
  avatar?: string | null;
  roles?: string[];
  permissions?: string[];
  [key: string]: unknown;
};

export type Profile = {
  id?: number | string;
  user_id: number | string;
  name: string;
  email: string;
  phone?: string | null;
  father_name?: string | null;
  mother_name?: string | null;
  present_address?: string | null;
  permanent_address?: string | null;
  education?: string | null;
  blood_group?: string | null;
  bio?: string | null;
  avatar_url?: string | null;
  has_uploaded_avatar?: boolean;
  status?: string;
  priority?: number;
  designation?: string | null;
  designation_id?: number | string | null;
  created_at?: string;
  updated_at?: string;
};

export type Category = { id: number | string; name: string; slug: string };

export type Work = {
  id: number | string;
  title: string;
  description: string;
  status: string;
  is_published: boolean;
  votes_count: number;
  required_votes: number;
  vote_progress: number;
  cover_url?: string | null;
  images?: Array<{ id: number | string; url: string; thumb_url?: string | null; name?: string | null }>;
  category?: string | null;
  submitted_name?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  user?: { id?: number | string; name?: string | null };
  has_voted?: boolean;
  updates?: Array<{
    id: number | string;
    title: string;
    description: string;
    author?: string | null;
    created_at?: string | null;
  }>;
};

export type Member = {
  id: number | string;
  user_id: number | string;
  name?: string | null;
  designation?: string | null;
  designation_order?: number | null;
  priority: number;
  bio?: string | null;
  avatar_url?: string | null;
  blood_group?: string | null;
};

export type WorkCategory = {
  id: number;
  name: string;
  slug: string;
};

export type PaginatedWorks = {
  works: Work[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

export class ApiError extends Error {
  status: number;
  payload: ApiResponse;

  constructor(message: string, status = 500, payload: ApiResponse = {}) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.payload = payload;
  }
}

export function getStoredToken() {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(TOKEN_KEY);
}

export function clearStoredToken() {
  if (typeof window !== "undefined") window.localStorage.removeItem(TOKEN_KEY);
}

function storeToken(token: string) {
  if (typeof window !== "undefined") window.localStorage.setItem(TOKEN_KEY, token);
}

function errorMessage(payload: ApiResponse, fallback: string) {
  if (payload.message) return payload.message;
  if (payload.errors) {
    const first = Object.values(payload.errors).flat()[0];
    if (first) return String(first);
  }
  return fallback;
}

const API_REQUEST_TIMEOUT_MS = 5000;
const API_GET_RETRIES = 1;

function sleep(ms: number) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function request<T>(path: string, init: RequestInit = {}, authenticated = false): Promise<T> {
  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");
  if (init.body && !(typeof FormData !== "undefined" && init.body instanceof FormData) && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  const token = getStoredToken();
  if (authenticated && token) headers.set("Authorization", `Bearer ${token}`);

  const method = (init.method || "GET").toUpperCase();
  const maxAttempts = method === "GET" ? API_GET_RETRIES + 1 : 1;
  let lastNetworkError: unknown = null;

  for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), API_REQUEST_TIMEOUT_MS);

    try {
      const response = await fetch(`${API_BASE_URL}/api${path}`, {
        ...init,
        headers,
        credentials: "include",
        cache: "no-store",
        signal: controller.signal,
      });

      const text = await response.text();
      let payload: ApiResponse = {};
      try {
        payload = text ? JSON.parse(text) : {};
      } catch {
        payload = {};
      }

      if (!response.ok) {
        throw new ApiError(
          errorMessage(payload, response.status === 401 ? "আপনার লগইন সেশন বৈধ নয়।" : "অনুরোধটি সম্পন্ন করা যায়নি।"),
          response.status,
          payload
        );
      }

      return payload as T;
    } catch (error) {
      if (error instanceof ApiError) throw error;
      lastNetworkError = error;
      if (attempt + 1 < maxAttempts) await sleep(250);
    } finally {
      clearTimeout(timeoutId);
    }
  }

  throw new ApiError("সার্ভারের সাথে সংযোগ করা যাচ্ছে না। কিছুক্ষণ পরে আবার চেষ্টা করুন।", 0, {
    message: lastNetworkError instanceof Error && lastNetworkError.name === "AbortError"
      ? "সার্ভার সাড়া দিতে সময় নিচ্ছে।"
      : undefined,
  });
}

const emptyWorks = (): PaginatedWorks => ({
  works: [],
  meta: { current_page: 1, last_page: 1, per_page: 0, total: 0 },
});

export async function getWorks(params: { page?: number; perPage?: number; search?: string; status?: string; categoryId?: number } = {}) {
  const query = new URLSearchParams();
  if (params.page) query.set("page", String(params.page));
  if (params.perPage) query.set("per_page", String(params.perPage));
  if (params.search) query.set("search", params.search);
  if (params.status) query.set("status", params.status);
  if (params.categoryId) query.set("category_id", String(params.categoryId));
  try {
    return await request<PaginatedWorks>(`/works${query.toString() ? `?${query.toString()}` : ""}`, { method: "GET" }, Boolean(getStoredToken()));
  } catch (error) {
    if (error instanceof ApiError && error.status === 0) return emptyWorks();
    throw error;
  }
}

export async function getPendingWorks() {
  try {
    const payload = await request<{ works: Work[] }>("/works/pending", { method: "GET" }, true);
    return payload.works;
  } catch (error) {
    if (error instanceof ApiError && error.status === 0) return [];
    throw error;
  }
}

export async function getWork(id: number | string) {
  const encoded = encodeURIComponent(String(id));
  const token = getStoredToken();
  if (token) {
    try {
      const payload = await request<{ work: Work }>(`/works/${encoded}/view`, { method: "GET" }, true);
      return payload.work;
    } catch (error) {
      if (!(error instanceof ApiError) || error.status !== 401) throw error;
    }
  }
  const payload = await request<{ work: Work }>(`/works/${encoded}`);
  return payload.work;
}

export async function voteWork(id: number | string) {
  return request<{ message: string; votes_count: number; required: number; status: string; approved: boolean; is_published: boolean; has_voted: boolean }>(
    `/works/${encodeURIComponent(String(id))}/vote`, { method: "POST" }, true
  );
}

export async function undoVoteWork(id: number | string) {
  return request<{ message: string; votes_count: number; required: number; status: string; approved: boolean; is_published: boolean; has_voted: boolean }>(
    `/works/${encodeURIComponent(String(id))}/vote`, { method: "DELETE" }, true
  );
}

export async function getMembers() {
  try {
    const payload = await request<{ members: Member[] }>("/members");
    return payload.members;
  } catch (error) {
    if (error instanceof ApiError && error.status === 0) return [];
    throw error;
  }
}

export async function getWorkCategories() {
  try {
    const payload = await request<{ categories: WorkCategory[] }>("/categories");
    return payload.categories;
  } catch (error) {
    if (error instanceof ApiError && error.status === 0) return [];
    throw error;
  }
}

export async function getProfile() {
  const payload = await request<ApiResponse>("/profile", { method: "GET" }, true);
  return payload.profile as Profile;
}

export async function updateProfile(fields: Record<string, string>, image?: File | null) {
  const body = new FormData();
  Object.entries(fields).forEach(([key, value]) => body.append(key, value));
  if (image) body.append("image", image);
  const payload = await request<ApiResponse>("/profile", { method: "POST", body }, true);
  return payload.profile as Profile;
}

export async function deleteAvatar() {
  const payload = await request<ApiResponse>("/profile/avatar", { method: "DELETE" }, true);
  return payload.profile as Profile;
}

export async function getCategories() {
  try {
    const payload = await request<{ categories: Category[] }>("/categories");
    return payload.categories;
  } catch (error) {
    if (error instanceof ApiError && error.status === 0) return [];
    throw error;
  }
}

export async function submitWork(form: FormData) {
  return request<{ work: Work; message: string }>("/works", { method: "POST", body: form }, Boolean(getStoredToken()));
}

export async function login(email: string, password: string) {
  const payload = await request<ApiResponse>("/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });
  const token = payload.access_token || payload.token ||
    (payload.data && typeof payload.data === "object" && "access_token" in payload.data ? String((payload.data as Record<string, unknown>).access_token) : null) ||
    (payload.data && typeof payload.data === "object" && "token" in payload.data ? String((payload.data as Record<string, unknown>).token) : null);
  if (token) storeToken(token);
  const user = (payload.user || (payload.data && typeof payload.data === "object" && "user" in payload.data ? (payload.data as Record<string, unknown>).user : null)) as AuthUser | null;
  return { token, user };
}

export async function getMe() {
  const payload = await request<ApiResponse>("/auth/me", { method: "GET" }, true);
  return (payload.user || payload.data || payload) as AuthUser;
}

export async function logout(all = false) {
  try {
    await request<ApiResponse>(all ? "/auth/logout-all" : "/auth/logout", { method: "POST" }, true);
  } finally {
    clearStoredToken();
  }
}

export type ContactPayload = {
  name: string;
  email: string;
  phone?: string;
  subject: string;
  message: string;
};

export async function sendContactMessage(data: ContactPayload) {
  return request<ApiResponse>("/contact", { method: "POST", body: JSON.stringify(data) });
}

export async function completeGoogleLogin(code: string) {
  const payload = await request<ApiResponse>("/auth/google/exchange", { method: "POST", body: JSON.stringify({ code }) });
  const token = payload.access_token || payload.token ||
    (payload.data && typeof payload.data === "object" && "access_token" in payload.data ? String((payload.data as Record<string, unknown>).access_token) : null) ||
    (payload.data && typeof payload.data === "object" && "token" in payload.data ? String((payload.data as Record<string, unknown>).token) : null);
  if (!token) throw new ApiError("Google লগইনের access token পাওয়া যায়নি।", 500, payload);
  storeToken(token);
  const user = (payload.user || (payload.data && typeof payload.data === "object" && "user" in payload.data ? (payload.data as Record<string, unknown>).user : null)) as AuthUser | null;
  if (!user) throw new ApiError("Google লগইনের user data পাওয়া যায়নি।", 500, payload);
  return { token, user };
}

export async function register(name: string, email: string, password: string, passwordConfirmation: string) {
  const payload = await request<ApiResponse>("/auth/register", {
    method: "POST",
    body: JSON.stringify({ name, email, password, password_confirmation: passwordConfirmation }),
  });
  const token = payload.access_token || payload.token ||
    (payload.data && typeof payload.data === "object" && "access_token" in payload.data ? String((payload.data as Record<string, unknown>).access_token) : null) ||
    (payload.data && typeof payload.data === "object" && "token" in payload.data ? String((payload.data as Record<string, unknown>).token) : null);
  if (token) storeToken(token);
  const user = (payload.user || (payload.data && typeof payload.data === "object" && "user" in payload.data ? (payload.data as Record<string, unknown>).user : null)) as AuthUser | null;
  return { token, user };
}
