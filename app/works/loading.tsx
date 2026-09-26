export default function WorksLoading() {
  return (
    <main className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8" aria-busy="true" aria-label="লোড হচ্ছে">
      <div className="max-w-3xl animate-pulse">
        <div className="h-5 w-28 rounded-full bg-zinc-200" />
        <div className="mt-3 h-12 w-3/4 rounded-2xl bg-zinc-200" />
        <div className="mt-4 h-7 w-full max-w-2xl rounded-xl bg-zinc-200" />
      </div>
      <div className="mt-10 grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
        <div className="animate-pulse rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 sm:p-8">
          <div className="h-7 w-40 rounded-xl bg-zinc-200" />
          <div className="mt-6 space-y-4">
            <div className="h-12 rounded-2xl bg-zinc-200" />
            <div className="h-12 rounded-2xl bg-zinc-200" />
            <div className="h-12 rounded-2xl bg-zinc-200" />
            <div className="h-36 rounded-2xl bg-zinc-200" />
          </div>
        </div>
        <div className="grid gap-5 sm:grid-cols-2">
          {Array.from({ length: 4 }).map((_, index) => (
            <div key={index} className="animate-pulse rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
              <div className="h-5 w-2/3 rounded-lg bg-zinc-200" />
              <div className="mt-4 h-4 w-full rounded-lg bg-zinc-200" />
              <div className="mt-2 h-4 w-5/6 rounded-lg bg-zinc-200" />
            </div>
          ))}
        </div>
      </div>
    </main>
  );
}
