import type { Metadata } from "next";
import Link from "next/link";
import { getWork, ApiError, type Work } from "@/lib/api";

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: { params: Promise<{ id: string }> }): Promise<Metadata> {
  const { id } = await params;
  try {
    const work = await getWork(id);
    return { title: work.title, description: work.description.slice(0, 160) };
  } catch {
    return { title: "কার্যক্রম" };
  }
}

export default async function ActivityDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  let work: Work | null = null;
  let unavailable = false;

  try {
    work = await getWork(id);
  } catch (error) {
    if (error instanceof ApiError && error.status === 0) unavailable = true;
    else throw error;
  }

  if (!work) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6 lg:px-8">
        <Link href="/activities" className="text-sm font-semibold text-emerald-700">← সব কার্যক্রম</Link>
        <h1 className="mt-6 text-3xl font-bold">{unavailable ? "তথ্য এখন পাওয়া যাচ্ছে না" : "কার্যক্রমটি পাওয়া যায়নি"}</h1>
        <p className="mt-3 leading-7 text-zinc-500">
          {unavailable ? "Backend service সাময়িকভাবে অনুপলব্ধ। পরে আবার চেষ্টা করুন।" : "এই কার্যক্রমটি আর প্রকাশিত নেই বা পাওয়া যায়নি।"}
        </p>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
      <Link href="/activities" className="text-sm font-semibold text-emerald-700">← সব কার্যক্রম</Link>
      <article className="mt-6 overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm">
        {work.cover_url && <img src={work.cover_url} alt={work.title} className="max-h-[520px] w-full object-cover" />}
        <div className="p-7 sm:p-10">
          <div className="flex flex-wrap gap-2">
            {work.category && <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{work.category}</span>}
            <span className="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600">{work.status}</span>
          </div>
          <h1 className="mt-5 text-3xl font-bold sm:text-4xl">{work.title}</h1>
          <p className="mt-6 whitespace-pre-line text-base leading-8 text-zinc-600">{work.description}</p>
          <div className="mt-8 grid gap-4 sm:grid-cols-3">
            <div className="rounded-2xl bg-zinc-50 p-5"><div className="text-xs text-zinc-500">ভোট</div><div className="mt-2 text-xl font-bold">{work.votes_count}/{work.required_votes}</div></div>
            <div className="rounded-2xl bg-zinc-50 p-5"><div className="text-xs text-zinc-500">অগ্রগতি</div><div className="mt-2 text-xl font-bold">{work.vote_progress}%</div></div>
            <div className="rounded-2xl bg-zinc-50 p-5"><div className="text-xs text-zinc-500">প্রস্তাবদাতা</div><div className="mt-2 font-semibold">{work.user?.name || work.submitted_name || "ফিউচার হোপ"}</div></div>
          </div>
          {work.updates && work.updates.length > 0 && (
            <section className="mt-10 border-t border-zinc-200 pt-8">
              <h2 className="text-2xl font-bold">সর্বশেষ আপডেট</h2>
              <div className="mt-5 space-y-4">
                {work.updates.map(update => (
                  <div key={update.id} className="rounded-2xl border border-zinc-200 p-5">
                    <h3 className="font-bold">{update.title}</h3>
                    <p className="mt-2 whitespace-pre-line leading-7 text-zinc-600">{update.description}</p>
                    {update.author && <p className="mt-3 text-xs text-zinc-500">{update.author}</p>}
                  </div>
                ))}
              </div>
            </section>
          )}
        </div>
      </article>
    </div>
  );
}
