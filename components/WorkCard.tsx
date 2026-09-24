import Link from "next/link";
import type { Work } from "@/lib/api";

const statusLabels: Record<string, string> = {
  suggested: "প্রস্তাবিত",
  voting: "ভোট চলছে",
  approved: "অনুমোদিত",
  running: "চলমান",
  upcoming: "শিগগির শুরু",
  completed: "সম্পন্ন",
};

export default function WorkCard({ work }: { work: Work }) {
  return (
    <article className="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
      {work.cover_url ? (
        <img src={work.cover_url} alt={work.title} className="h-52 w-full object-cover" />
      ) : (
        <div className="flex h-52 items-center justify-center bg-emerald-50 text-sm font-semibold text-emerald-700">
          {work.category || "ফিউচার হোপ"}
        </div>
      )}
      <div className="p-6">
        <div className="flex flex-wrap items-center gap-2">
          {work.category && <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{work.category}</span>}
          <span className="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600">{statusLabels[work.status] || work.status}</span>
        </div>
        <h2 className="mt-4 line-clamp-2 text-xl font-bold text-zinc-950">{work.title}</h2>
        <p className="mt-3 line-clamp-3 whitespace-pre-line text-sm leading-7 text-zinc-500">{work.description}</p>
        <div className="mt-5 flex items-center justify-between gap-4 text-xs text-zinc-500">
          <span>{work.user?.name || work.submitted_name || "ফিউচার হোপ"}</span>
          <span>{work.votes_count}/{work.required_votes} ভোট</span>
        </div>
        <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-100">
          <div className="h-full rounded-full bg-emerald-500" style={{ width: `${work.vote_progress}%` }} />
        </div>
        <Link href={`/activities/${work.id}`} className="mt-5 inline-flex rounded-xl border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
          বিস্তারিত দেখুন
        </Link>
      </div>
    </article>
  );
}