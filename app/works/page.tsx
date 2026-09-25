"use client";

import Link from "next/link";
import WorkGallery from "@/components/WorkGallery";
import { ChangeEvent, FormEvent, useCallback, useEffect, useState } from "react";
import AuthDialog from "@/components/AuthDialog";
import {
  ApiError,
  getCategories,
  getPendingWorks,
  getWorks,
  submitWork,
  undoVoteWork,
  voteWork,
  type Category,
  type Work,
} from "@/lib/api";
import { useAuth } from "@/components/AuthProvider";

const statusLabels: Record<string, string> = {
  suggested: "প্রস্তাবিত",
  voting: "ভোট চলছে",
  approved: "অনুমোদিত",
  running: "চলমান",
  upcoming: "শীঘ্রই",
  completed: "সম্পন্ন",
  rejected: "বাতিল",
};

export default function WorksPage() {
  const { user, isMember, loading: authLoading } = useAuth();
  const [works, setWorks] = useState<Work[]>([]);
  const [pending, setPending] = useState<Work[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [submittedWork, setSubmittedWork] = useState<Work | null>(null);
  const [authOpen, setAuthOpen] = useState(false);
  const [form, setForm] = useState({
    title: "",
    description: "",
    category_id: "",
    submitted_name: "",
    submitted_email: "",
  });
  const [images, setImages] = useState<File[]>([]);
  const [busy, setBusy] = useState(false);
  const [votingId, setVotingId] = useState<string | number | null>(null);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  const load = useCallback(async () => {
    setError("");

    try {
      const [worksResponse, cats] = await Promise.all([
        getWorks({ perPage: 50 }),
        getCategories(),
      ]);

      setWorks(worksResponse.works.filter((work) => work.is_published));
      setCategories(cats);

      if (isMember) {
        try {
          const pendingWorks = await getPendingWorks();
          setPending(pendingWorks.filter((work) => !work.is_published));
        } catch (e) {
          setPending([]);
          throw e;
        }
      } else {
        setPending([]);
      }
    } catch (e) {
      setError(
        e instanceof ApiError
          ? e.message
          : "তথ্য আনা যায়নি। আবার চেষ্টা করুন।",
      );
    }
  }, [isMember]);

  // Data fetching is a legitimate external synchronization; state updates happen from the async request lifecycle.
  useEffect(() => {
    if (!authLoading) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      void load();
    }
  }, [authLoading, load]);

  function chooseImages(e: ChangeEvent<HTMLInputElement>) {
    const files = Array.from(e.target.files ?? []);

    if (files.length > 10) {
      setError("একসাথে সর্বোচ্চ ১০টি ছবি নির্বাচন করতে পারবেন।");
      return;
    }

    if (files.some((file) => !file.type.startsWith("image/"))) {
      setError("শুধু image file নির্বাচন করুন।");
      return;
    }

    if (files.some((file) => file.size > 5 * 1024 * 1024)) {
      setError("প্রতিটি ছবির আকার সর্বোচ্চ ৫ MB হতে হবে।");
      return;
    }

    const total = files.reduce((sum, file) => sum + file.size, 0);
    if (total > 30 * 1024 * 1024) {
      setError("সব ছবি মিলিয়ে সর্বোচ্চ ৩০ MB আপলোড করা যাবে।");
      return;
    }

    setImages(files);
    setError("");
  }

  async function submit(e: FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError("");
    setMessage("");
    setSubmittedWork(null);

    try {
      const body = new FormData();

      Object.entries(form).forEach(([key, value]) => {
        if (value.trim()) {
          body.append(key, value.trim());
        }
      });

      images.forEach((image) => body.append("images[]", image));

      const result = await submitWork(body);

      setMessage(result.message);
      setSubmittedWork(result.work);
      setForm({
        title: "",
        description: "",
        category_id: "",
        submitted_name: "",
        submitted_email: "",
      });
      setImages([]);

      await load();
    } catch (e) {
      setError(
        e instanceof ApiError
          ? e.message
          : "প্রস্তাব জমা দেওয়া যায়নি। আবার চেষ্টা করুন।",
      );
    } finally {
      setBusy(false);
    }
  }

  async function vote(id: string | number, undo = false) {
    if (!user) {
      setAuthOpen(true);
      return;
    }

    if (!isMember) {
      setError("এই কাজের জন্য সদস্যের ভোট দেওয়ার অনুমতি প্রয়োজন।");
      return;
    }

    setVotingId(id);
    setError("");
    setMessage("");

    try {
      const result = undo ? await undoVoteWork(id) : await voteWork(id);
      const sourceWork =
        works.find((work) => work.id === id) ??
        pending.find((work) => work.id === id);

      const updatedWork = sourceWork
        ? {
            ...sourceWork,
            votes_count: result.votes_count,
            required_votes: result.required,
            status: result.status,
            is_published: result.is_published,
            vote_progress: Math.min(
              100,
              Math.round(
                (result.votes_count / Math.max(1, result.required)) * 100,
              ),
            ),
            has_voted: result.has_voted,
          }
        : null;

      setMessage(result.message);

      if (result.is_published) {
        setPending((current) => current.filter((work) => work.id !== id));
        setWorks((current) => {
          const exists = current.some((work) => work.id === id);
          if (!updatedWork) return current;
          return exists
            ? current.map((work) => (work.id === id ? updatedWork : work))
            : [updatedWork, ...current];
        });
      } else {
        setWorks((current) => current.filter((work) => work.id !== id));
        setPending((current) => {
          const exists = current.some((work) => work.id === id);
          if (!updatedWork) return current;
          return exists
            ? current.map((work) => (work.id === id ? updatedWork : work))
            : [updatedWork, ...current];
        });
      }

    } catch (e) {
      setError(
        e instanceof ApiError
          ? e.message
          : "ভোট দেওয়া যায়নি। আবার চেষ্টা করুন।",
      );
    } finally {
      setVotingId(null);
    }
  }

  return (
    <main className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
      <div className="max-w-3xl">
        <p className="text-sm font-bold text-emerald-700">কাজের প্রস্তাব</p>
        <h1 className="mt-2 text-4xl font-bold tracking-tight sm:text-5xl">
          একটি ভালো কাজের প্রস্তাব দিন
        </h1>
        <p className="mt-4 text-lg leading-8 text-zinc-500">
          ছোট করে বলুন—কী কাজ করা দরকার, কেন দরকার এবং চাইলে একটি ছবি দিন।
          Visitor বা logged-in user দুজনই প্রস্তাব দিতে পারবেন।
        </p>
      </div>

      <div className="mt-10 grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
        <form
          onSubmit={submit}
          className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 sm:p-8"
        >
          <div className="flex items-start justify-between gap-4">
            <div>
              <h2 className="text-2xl font-bold">প্রস্তাব জমা দিন</h2>
              <p className="mt-1 text-sm text-zinc-500">
                {user ? "আপনার account দিয়ে জমা হচ্ছে।" : "Visitor হিসেবেও জমা দিতে পারবেন।"}
              </p>
            </div>
            {user && (
              <span className="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                {isMember ? "সদস্য" : "লগইন"}
              </span>
            )}
          </div>

          <div className="mt-6 space-y-4">
            {!user && (
              <>
                <input
                  value={form.submitted_name}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, submitted_name: e.target.value }))
                  }
                  placeholder="আপনার নাম (ঐচ্ছিক)"
                  className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white"
                />
                <input
                  value={form.submitted_email}
                  onChange={(e) =>
                    setForm((f) => ({ ...f, submitted_email: e.target.value }))
                  }
                  type="email"
                  placeholder="ইমেইল (ঐচ্ছিক)"
                  className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white"
                />
              </>
            )}

            <input
              required
              value={form.title}
              onChange={(e) =>
                setForm((f) => ({ ...f, title: e.target.value }))
              }
              placeholder="কাজের নাম"
              className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white"
            />

            <select
              value={form.category_id}
              onChange={(e) =>
                setForm((f) => ({ ...f, category_id: e.target.value }))
              }
              className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none transition focus:border-emerald-500 focus:bg-white"
            >
              <option value="">ক্যাটাগরি নির্বাচন করুন</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>

            <textarea
              required
              rows={6}
              value={form.description}
              onChange={(e) =>
                setForm((f) => ({ ...f, description: e.target.value }))
              }
              placeholder="কাজটি কী এবং কেন দরকার—সংক্ষেপে লিখুন"
              className="w-full resize-y rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 leading-7 outline-none transition focus:border-emerald-500 focus:bg-white"
            />

            <label className="block rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-sm font-semibold text-zinc-600">
              ছবি (ঐচ্ছিক, সর্বোচ্চ ১০টি)
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                multiple
                onChange={chooseImages}
                className="mt-2 block w-full text-xs"
              />
              {images.length > 0 && (
                <p className="mt-2 text-xs text-zinc-500">{images.length}টি ছবি নির্বাচিত</p>
              )}
            </label>

            {error && (
              <div
                role="alert"
                className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
              >
                {error}
              </div>
            )}

            {message && (
              <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {message}
              </div>
            )}

            {submittedWork && (
              <div className="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                <p className="text-sm font-semibold text-zinc-700">
                  কাজটি তৈরি হয়েছে:{" "}
                  <span className="font-bold text-zinc-950">
                    {submittedWork.title}
                  </span>
                </p>
                <Link
                  href={"/works/" + submittedWork.id}
                  className="mt-3 inline-flex text-sm font-bold text-emerald-700 hover:underline"
                >
                  কাজের বিস্তারিত দেখুন →
                </Link>
              </div>
            )}

            <button
              disabled={busy}
              className="w-full rounded-2xl bg-emerald-600 px-5 py-3.5 font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {busy ? "জমা হচ্ছে..." : "প্রস্তাব জমা দিন"}
            </button>
          </div>
        </form>

        <section>
          <div className="flex items-end justify-between gap-4">
            <div>
              <p className="text-sm font-bold text-emerald-700">কাজসমূহ</p>
              <h2 className="mt-1 text-2xl font-bold">প্রকাশিত কাজ</h2>
            </div>
            <span className="rounded-full bg-zinc-100 px-3 py-1 text-xs font-bold text-zinc-500">
              {works.length}টি
            </span>
          </div>

          <div className="mt-5 space-y-4">
            {works.length === 0 ? (
              <div className="rounded-3xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500">
                এখনও কোনো কাজ প্রকাশিত হয়নি।
              </div>
            ) : (
              works.map((work) => (
                <WorkCard
                  key={work.id}
                  work={work}
                  onVote={vote}
                  voting={votingId === work.id}
                  isMember={isMember}
                  user={Boolean(user)}
                />
              ))
            )}
          </div>

          {!authLoading && isMember && (
            <div className="mt-12">
              <div className="flex items-end justify-between gap-4">
                <div>
                  <p className="text-sm font-bold text-emerald-700">
                    সদস্যদের ভোট
                  </p>
                  <h2 className="mt-1 text-2xl font-bold">ভোট চলছে</h2>
                  <p className="mt-1 text-sm leading-6 text-zinc-500">
                    প্রকাশিত হওয়ার আগে প্রয়োজনীয় ভোট সংগ্রহ করা হচ্ছে। আপনি ভোট দিলে কাজটি এখানেই থাকবে এবং আপনার ভোটের অবস্থা দেখা যাবে।
                  </p>
                </div>
                <span className="shrink-0 rounded-full bg-zinc-100 px-3 py-1 text-xs font-bold text-zinc-500">
                  {pending.length}টি
                </span>
              </div>

              <div className="mt-5 space-y-4">
                {pending.length === 0 ? (
                  <div className="rounded-3xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500">
                    এই মুহূর্তে ভোটিংয়ের কোনো কাজ নেই।
                  </div>
                ) : (
                  pending.map((work) => (
                    <WorkCard
                      key={work.id}
                      work={work}
                      onVote={vote}
                      voting={votingId === work.id}
                      isMember={isMember}
                      user={Boolean(user)}
                    />
                  ))
                )}
              </div>
            </div>
          )}
        </section>
      </div>

      <AuthDialog open={authOpen} onClose={() => setAuthOpen(false)} />
    </main>
  );
}

function WorkCard({
  work,
  onVote,
  voting,
  isMember,
  user,
}: {
  work: Work;
  onVote: (id: string | number, undo?: boolean) => void;
  voting: boolean;
  isMember: boolean;
  user: boolean;
}) {
  return (
    <article className="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-zinc-200 transition hover:-translate-y-0.5 hover:shadow-md">
      <WorkGallery images={work.images} coverUrl={work.cover_url} title={work.title} />

      <div className="p-6">
        <div className="flex items-center justify-between gap-3">
          <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
            {work.category || "সাধারণ"}
          </span>
          <span className="text-xs font-semibold text-zinc-400">
            {statusLabels[work.status] || work.status}
          </span>
        </div>

        <Link
          href={"/works/" + work.id}
          className="mt-4 block text-xl font-bold hover:text-emerald-700"
        >
          {work.title}
        </Link>

        <p className="mt-2 line-clamp-3 leading-7 text-zinc-500">
          {work.description}
        </p>

        <div className="mt-5 border-t border-zinc-100 pt-4">
          <div className="flex items-center justify-between gap-4">
            <span className="text-xs font-semibold text-zinc-500">
              {work.votes_count}/{work.required_votes} ভোট
            </span>
            <Link
              href={"/works/" + work.id}
              className="text-sm font-bold text-emerald-700 hover:underline"
            >
              বিস্তারিত →
            </Link>
          </div>

          <div className="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100">
            <div
              className="h-full rounded-full bg-emerald-500 transition-all"
              style={{ width: work.vote_progress + "%" }}
            />
          </div>

          {work.has_voted === true && (
            <div className="mt-3 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
              আপনার ভোট দেওয়া আছে
            </div>
          )}
        </div>

        {work.has_voted === true ? (
          <div className="mt-4 flex justify-end">
            <button
              type="button"
              disabled={voting}
              onClick={() => onVote(work.id, true)}
              title="আপনার ভোট বাতিল করুন"
              aria-label="এই কাজের ভোট বাতিল করুন"
              className="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:border-red-300 hover:bg-red-100 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <span aria-hidden="true">×</span>
              {voting ? "বাতিল হচ্ছে..." : "ভোট বাতিল"}
            </button>
          </div>
        ) : (
          <button
            type="button"
            disabled={voting}
            onClick={() => onVote(work.id, false)}
            className="mt-4 w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {voting ? "ভোট হচ্ছে..." : user && isMember ? "এই কাজে ভোট দিন" : "সদস্য হিসেবে ভোট দিন"}
          </button>
        )}      </div>
    </article>
  );
}
