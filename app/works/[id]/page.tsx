"use client";

import Link from "next/link";
import WorkGallery from "@/components/WorkGallery";
import { useParams } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import AuthDialog from "@/components/AuthDialog";
import { useAuth } from "@/components/AuthProvider";
import { ApiError, getWork, undoVoteWork, voteWork, type Work } from "@/lib/api";

const statusLabels: Record<string, string> = {
  suggested: "প্রস্তাবিত",
  voting: "ভোট চলছে",
  approved: "অনুমোদিত",
  running: "চলমান",
  upcoming: "শীঘ্রই",
  completed: "সম্পন্ন",
  rejected: "বাতিল",
};

export default function WorkDetailPage() {
  const params = useParams<{ id: string }>();
  const id = params?.id;
  const { user, isMember, loading: authLoading } = useAuth();

  const [work, setWork] = useState<Work | null>(null);
  const [loading, setLoading] = useState(true);
  const [voting, setVoting] = useState(false);
  const [authOpen, setAuthOpen] = useState(false);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  const load = useCallback(async () => {
    if (!id) return;

    setLoading(true);
    setError("");

    try {
      setWork(await getWork(id));
    } catch (e) {
      setWork(null);
      setError(
        e instanceof ApiError
          ? e.status === 404
            ? "এই কাজটি পাওয়া যায়নি অথবা এটি আপনার দেখার অনুমতির মধ্যে নেই।"
            : e.message
          : "কাজের তথ্য আনা যায়নি।",
      );
    } finally {
      setLoading(false);
    }
  }, [id]);

  // Data fetching is a legitimate external synchronization; state updates happen from the async request lifecycle.
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    void load();
  }, [load]);

  async function vote(undo = false) {
    if (!user) {
      setAuthOpen(true);
      return;
    }

    if (!isMember) {
      setError("এই কাজে ভোট দেওয়ার জন্য সদস্যের অনুমতি প্রয়োজন।");
      return;
    }

    if (!work || (work.is_published && !undo)) {
      return;
    }

    setVoting(true);
    setError("");
    setMessage("");

    try {
      const result = undo ? await undoVoteWork(work.id) : await voteWork(work.id);

      setMessage(result.message);
      setWork((current) =>
        current
          ? {
              ...current,
              votes_count: result.votes_count,
              vote_progress: Math.min(
                100,
                Math.round((result.votes_count / Math.max(1, result.required)) * 100),
              ),
              status: result.status,
              is_published: result.is_published,
              has_voted: result.has_voted,
            }
          : current,
      );
    } catch (e) {
      setError(
        e instanceof ApiError
          ? e.message
          : "ভোট দেওয়া যায়নি। আবার চেষ্টা করুন।",
      );
    } finally {
      setVoting(false);
    }
  }

  if (loading || authLoading) {
    return (
      <main className="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
        <div className="animate-pulse space-y-5">
          <div className="h-7 w-28 rounded-full bg-zinc-200" />
          <div className="h-12 w-3/4 rounded-2xl bg-zinc-200" />
          <div className="h-72 rounded-3xl bg-zinc-200" />
          <div className="h-32 rounded-3xl bg-zinc-200" />
        </div>
      </main>
    );
  }

  if (!work) {
    return (
      <main className="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
        <p className="text-sm font-bold text-red-600">কাজটি দেখা যাচ্ছে না</p>
        <h1 className="mt-2 text-3xl font-bold">এই কাজটি পাওয়া যায়নি</h1>
        <p className="mt-3 text-zinc-500">{error}</p>
        <Link
          href="/works"
          className="mt-7 inline-flex rounded-2xl bg-zinc-950 px-5 py-3 font-bold text-white hover:bg-zinc-800"
        >
          কাজের পাতায় ফিরে যান
        </Link>
        <AuthDialog open={authOpen} onClose={() => setAuthOpen(false)} />
      </main>
    );
  }

  const canVote = Boolean(user) && isMember && (!work.is_published || work.has_voted === true);

  return (
    <main className="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
      <Link
        href="/works"
        className="inline-flex items-center text-sm font-bold text-zinc-500 hover:text-zinc-950"
      >
        ← সব কাজ
      </Link>

      <article className="mt-5 overflow-hidden rounded-[2rem] bg-white shadow-sm ring-1 ring-zinc-200">
        <WorkGallery images={work.images} coverUrl={work.cover_url} title={work.title} />

        <div className="p-6 sm:p-10">
          <div className="flex flex-wrap items-center gap-2">
            <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
              {work.category || "সাধারণ"}
            </span>
            <span className="rounded-full bg-zinc-100 px-3 py-1 text-xs font-bold text-zinc-600">
              {statusLabels[work.status] || work.status}
            </span>
            {work.is_published && (
              <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                প্রকাশিত
              </span>
            )}
          </div>

          <h1 className="mt-5 text-3xl font-bold tracking-tight sm:text-5xl">
            {work.title}
          </h1>

          <p className="mt-5 whitespace-pre-wrap text-base leading-8 text-zinc-600 sm:text-lg">
            {work.description}
          </p>

          <div className="mt-8 rounded-3xl bg-zinc-50 p-5 sm:p-6">
            <div className="flex items-center justify-between gap-4">
              <div>
                <p className="text-sm font-semibold text-zinc-500">ভোটের অগ্রগতি</p>
                <p className="mt-1 text-2xl font-bold text-zinc-950">
                  {work.votes_count}/{work.required_votes}
                </p>
              </div>
              <div className="text-right">
                <p className="text-sm font-semibold text-zinc-500">অগ্রগতি</p>
                <p className="mt-1 text-2xl font-bold text-emerald-700">
                  {work.vote_progress}%
                </p>
              </div>
            </div>

            <div className="mt-4 h-3 overflow-hidden rounded-full bg-zinc-200">
              <div
                className="h-full rounded-full bg-emerald-500 transition-all"
                style={{ width: work.vote_progress + "%" }}
              />
            </div>
          </div>

          {work.submitted_name && (
            <p className="mt-6 text-sm text-zinc-500">
              প্রস্তাবদাতা:{" "}
              <span className="font-semibold text-zinc-800">
                {work.submitted_name}
              </span>
            </p>
          )}

          {message && (
            <div className="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
              {message}
            </div>
          )}

          {error && (
            <div className="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
              {error}
            </div>
          )}

          {canVote && (
            <button
              type="button"
              disabled={voting}
              onClick={() => void vote(work.has_voted === true)}
              className="mt-7 w-full rounded-2xl bg-zinc-950 px-5 py-4 font-bold text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto sm:min-w-64"
            >
              {voting ? (work.has_voted ? "ভোট বাতিল হচ্ছে..." : "ভোট হচ্ছে...") : work.has_voted ? "ভোট বাতিল করুন" : "এই কাজে ভোট দিন"}
            </button>
          )}



          {!work.is_published && user && !isMember && (
            <div className="mt-7 rounded-2xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
              এই কাজের ভোটিং অংশে অংশ নিতে সদস্যের অনুমতি প্রয়োজন।
            </div>
          )}

          {!work.is_published && !user && (
            <button
              type="button"
              onClick={() => setAuthOpen(true)}
              className="mt-7 w-full rounded-2xl border border-zinc-300 px-5 py-4 font-bold text-zinc-900 hover:bg-zinc-50 sm:w-auto"
            >
              সদস্য হিসেবে লগইন করুন
            </button>
          )}
        </div>
      </article>

      <AuthDialog open={authOpen} onClose={() => setAuthOpen(false)} />
    </main>
  );
}
