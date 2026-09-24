import type { Metadata } from "next";
import { getMembers } from "@/lib/api";

export const metadata: Metadata = {
  title: "সদস্যবৃন্দ",
  description: "ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশনের অনুমোদিত সদস্য ও দায়িত্বশীলদের তথ্য।",
};
export const dynamic = "force-dynamic";

export default async function MembersPage() {
  const members = await getMembers();

  return (
    <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
      <div className="max-w-3xl">
        <p className="text-sm font-bold text-emerald-700">সদস্যবৃন্দ</p>
        <h1 className="mt-2 text-4xl font-bold sm:text-5xl">আমাদের সদস্য ও দায়িত্বশীলরা</h1>
        <p className="mt-5 leading-8 text-zinc-500">Backend-এ সক্রিয় ও অনুমোদিত সদস্যদের তথ্য অনুযায়ী এই তালিকা স্বয়ংক্রিয়ভাবে তৈরি হয়।</p>
      </div>

      {members.length > 0 ? (
        <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {members.map(member => (
            <article key={member.id} className="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
              <div className="flex items-center gap-4">
                {member.avatar_url ? (
                  <img src={member.avatar_url} alt={member.name || "সদস্য"} className="h-16 w-16 rounded-full object-cover" />
                ) : (
                  <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-xl font-bold text-emerald-700">
                    {(member.name || "?").trim().charAt(0)}
                  </div>
                )}
                <div className="min-w-0">
                  <h2 className="truncate text-xl font-bold">{member.name || "নাম দেওয়া হয়নি"}</h2>
                  <p className="mt-1 text-sm font-semibold text-emerald-700">{member.designation || "সদস্য"}</p>
                </div>
              </div>
              {member.bio && <p className="mt-5 whitespace-pre-line leading-7 text-zinc-500">{member.bio}</p>}
            </article>
          ))}
        </div>
      ) : (
        <div className="mt-12 rounded-3xl border border-zinc-200 bg-white px-6 py-16 text-center shadow-sm">
          <h2 className="text-2xl font-bold">এখনও কোনো অনুমোদিত সদস্য নেই</h2>
          <p className="mt-3 leading-7 text-zinc-500">Backend-এ সদস্যের profile অনুমোদিত হলে এখানে স্বয়ংক্রিয়ভাবে দেখা যাবে।</p>
        </div>
      )}
    </div>
  );
}