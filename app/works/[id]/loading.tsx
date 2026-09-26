export default function WorkDetailLoading() {
  return (
    <main className="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8" aria-busy="true" aria-label="লোড হচ্ছে">
      <div className="animate-pulse space-y-5">
        <div className="h-5 w-24 rounded-full bg-zinc-200" />
        <div className="h-12 w-3/4 rounded-2xl bg-zinc-200" />
        <div className="h-72 rounded-3xl bg-zinc-200" />
        <div className="h-32 rounded-3xl bg-zinc-200" />
      </div>
    </main>
  );
}
