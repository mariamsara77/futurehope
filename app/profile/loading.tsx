export default function ProfileLoading() {
  return (
    <main className="mx-auto max-w-5xl px-4 py-20 sm:px-6 lg:px-8" aria-busy="true" aria-label="লোড হচ্ছে">
      <div className="animate-pulse space-y-7">
        <div className="h-72 rounded-3xl bg-zinc-200" />
        <div className="h-52 rounded-3xl bg-zinc-200" />
        <div className="h-72 rounded-3xl bg-zinc-200" />
      </div>
    </main>
  );
}
