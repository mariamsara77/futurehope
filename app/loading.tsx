export default function Loading() {
  return (
    <main className="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-busy="true" aria-label="লোড হচ্ছে">
      <div className="animate-pulse space-y-6">
        <div className="h-7 w-28 rounded-full bg-zinc-200" />
        <div className="h-12 w-3/4 max-w-2xl rounded-2xl bg-zinc-200" />
        <div className="h-6 w-full max-w-3xl rounded-xl bg-zinc-200" />
        <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }).map((_, index) => (
            <div key={index} className="h-48 rounded-2xl bg-zinc-200" />
          ))}
        </div>
      </div>
    </main>
  );
}
