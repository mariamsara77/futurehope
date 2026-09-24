import type { Metadata } from "next";
import WorkCard from "@/components/WorkCard";
import { getWorks } from "@/lib/api";

export const metadata: Metadata = {
  title: "কার্যক্রম",
  description: "ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশনের প্রকাশিত কার্যক্রম ও উদ্যোগসমূহ।",
};
export const dynamic = "force-dynamic";

export default async function ActivitiesPage() {
  const result = await getWorks({ perPage: 12 });
  return (
    <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
      <div className="max-w-3xl">
        <p className="text-sm font-bold text-emerald-700">কার্যক্রম</p>
        <h1 className="mt-2 text-4xl font-bold sm:text-5xl">আমাদের প্রকাশিত উদ্যোগ</h1>
        <p className="mt-5 text-lg leading-8 text-zinc-500">প্রশাসনিক প্যানেল থেকে প্রকাশিত কাজ ও উদ্যোগগুলো এখানে স্বয়ংক্রিয়ভাবে প্রদর্শিত হয়।</p>
      </div>

      {result.works.length > 0 ? (
        <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {result.works.map(work => <WorkCard key={work.id} work={work} />)}
        </div>
      ) : (
        <div className="mt-12 rounded-3xl border border-zinc-200 bg-white px-6 py-16 text-center shadow-sm">
          <h2 className="text-2xl font-bold">এখনও কোনো প্রকাশিত কার্যক্রম নেই</h2>
          <p className="mx-auto mt-3 max-w-xl leading-7 text-zinc-500">Backend থেকে কোনো কাজ প্রকাশিত হলে সেটি এই পাতায় স্বয়ংক্রিয়ভাবে দেখা যাবে।</p>
        </div>
      )}

      {result.meta.last_page > 1 && (
        <p className="mt-8 text-center text-sm text-zinc-500">মোট {result.meta.total}টি প্রকাশিত কার্যক্রম</p>
      )}
    </div>
  );
}