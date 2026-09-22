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
  status?: "pending" | "active" | "rejected" | string;
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
  description?: string | null;
  status: string;
  is_published: boolean;
  votes_count: number;
  required_votes: number;
  vote_progress: number;
  cover_url?: string | null;
  category?: string | null;
  submitted_name?: string | null;
  created_at?: string | null;
  user?: { id?: number | string; name?: string | null };
};

export type Member = {
  id: number | string;
  user_id: number | string;
  name: string;
  designation?: string | null;
  designation_order?: number | null;
  priority: number;
  bio?: string | null;
  avatar_url?: string | null;
  blood_group?: string | null;
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
  return typeof window === "undefined" ? null : window.localStorage.getItem(TOKEN_KEY);
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

async function request<T>(path: string, init: RequestInit = {}, authenticated = false): Promise<T> {
  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");
  if (init.body && !headers.has("Content-Type") && !(init.body instanceof FormData)) headers.set("Content-Type", "application/json");

  const token = getStoredToken();
  if (authenticated && token) headers.set("Authorization", `Bearer ${token}`);

  let response: Response;
  try {
    response = await fetch(`${API_BASE_URL}/api${path}`, {
      ...init,
      headers,
      credentials: "include",
      cache: "no-store",
    });
  } catch {
    throw new ApiError("সার্ভারের সাথে সংযোগ করা যাচ্ছে না। কিছুক্ষণ পরে আবার চেষ্টা করুন।", 0);
  }

  const text = await response.text();
  let payload: ApiResponse = {};
  try { payload = text ? JSON.parse(text) : {}; } catch {}

  if (!response.ok) {
    if (response.status === 401) clearStoredToken();
    throw new ApiError(
      errorMessage(payload, response.status === 401 ? "আপনার লগইন সেশন বৈধ নয়।" : "অনুরোধটি সম্পন্ন করা যায়নি।"),
      response.status,
      payload,
    );
  }

  return payload as T;
}

export async function login(email: string, password: string) {
  clearStoredToken();
  const payload = await request<ApiResponse>("/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });
  const token = payload.token || payload.access_token;
  if (token) storeToken(token);
  return { token, user: payload.user as AuthUser | null };
}

export async function register(name: string, email: string, password: string, image?: File | null) {
  clearStoredToken();
  const body = new FormData();
  body.append("name", name);
  body.append("email", email);
  body.append("password", password);
  body.append("password_confirmation", password);
  if (image) body.append("image", image);

  const payload = await request<ApiResponse>("/auth/register", {
    method: "POST",
    body,
  });
  const token = payload.token || payload.access_token;
  if (token) storeToken(token);
  return { token, user: payload.user as AuthUser | null };
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
  const payload = await request<{ categories: Category[] }>("/categories");
  return payload.categories;
}

export async function submitWork(form: FormData) {
  const payload = await request<{ work: Work; message: string }>("/works", { method: "POST", body: form });
  return payload;
}

export async function getWorks(status?: string) {
  const query = status ? `?status=${encodeURIComponent(status)}` : "";
  const payload = await request<{ works: Work[] }>(`/works${query}`);
  return payload.works;
}

export async function getPendingWorks() {
  const payload = await request<{ works: Work[] }>("/works/pending", { method: "GET" }, true);
  return payload.works;
}

export async function voteWork(id: number | string) {
  return request<{ message: string; votes_count: number; required: number; status: string; approved: boolean }>(
    `/works/${id}/vote`,
    { method: "POST" },
    true,
  );
}

export async function getMembers() {
  const payload = await request<{ members: Member[] }>("/members");
  return payload.members;
}

export async function exchangeGoogleCode(code: string) {
  clearStoredToken();
  const payload = await request<ApiResponse>("/auth/google/exchange", {
    method: "POST",
    body: JSON.stringify({ code }),
  });
  const token = payload.token || payload.access_token;
  if (token) storeToken(token);
  return { token, user: payload.user as AuthUser | null };
}
