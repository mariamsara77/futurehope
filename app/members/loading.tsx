export default function MembersLoading() {
  return (
    <main className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-busy="true" aria-label="লোড হচ্ছে">
      <div className="max-w-3xl animate-pulse">
        <div className="h-5 w-24 rounded-full bg-zinc-200" />
        <div className="mt-3 h-12 w-3/4 rounded-2xl bg-zinc-200" />
        <div className="mt-5 h-7 w-full max-w-2xl rounded-xl bg-zinc-200" />
      </div>
      <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {Array.from({ length: 6 }).map((_, index) => (
          <article key={index} className="animate-pulse rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div className="flex items-center gap-4">
              <div className="h-16 w-16 shrink-0 rounded-full bg-zinc-200" />
              <div className="min-w-0 flex-1 space-y-2">
                <div className="h-5 w-2/3 rounded-lg bg-zinc-200" />
                <div className="h-4 w-1/2 rounded-lg bg-zinc-200" />
              </div>
            </div>
            <div className="mt-5 space-y-2">
              <div className="h-4 w-full rounded-lg bg-zinc-200" />
              <div className="h-4 w-5/6 rounded-lg bg-zinc-200" />
            </div>
          </article>
        ))}
      </div>
    </main>
  );
}
