import Link from "next/link";

export default function Footer() {
  return <footer className="border-t border-zinc-200 bg-white">
    <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
      <div><div className="flex items-center gap-3"><img src="/500.png" alt="" className="h-10 w-10 object-contain" /><div><p className="font-bold">ফিউচার হোপ</p><p className="text-xs text-zinc-500">অ্যান্ড হিউম্যানিটি ফাউন্ডেশন</p></div></div><p className="mt-4 max-w-sm text-sm leading-7 text-zinc-500">মানুষের পাশে থেকে শিক্ষা, মানবিক সহায়তা ও সামাজিক উন্নয়নের মাধ্যমে একটি সুন্দর ভবিষ্যৎ গড়ার প্রত্যয়।</p></div>
      <div><h2 className="font-bold">দ্রুত লিংক</h2><div className="mt-4 grid grid-cols-2 gap-3 text-sm text-zinc-500"><Link href="/about" className="hover:text-emerald-700">আমাদের সম্পর্কে</Link><Link href="/activities" className="hover:text-emerald-700">কার্যক্রম</Link><Link href="/members" className="hover:text-emerald-700">সদস্যবৃন্দ</Link><Link href="/contact" className="hover:text-emerald-700">যোগাযোগ</Link></div></div>
      <div><h2 className="font-bold">যোগাযোগ</h2><div className="mt-4 space-y-2 text-sm leading-6 text-zinc-500"><p>গ্রাম: ছোনবুনিয়া, ঘুটাবাছা, গোলবুনিয়া</p><p>পোস্ট: কালমেঘা, উপজেলা: পাথরঘাটা</p><p>জেলা: বরগুনা</p><a href="tel:01340792677" className="block font-semibold text-emerald-700">০১৩৪০-৭৯২৬৭৭</a></div></div>
    </div><div className="border-t border-zinc-100 py-5 text-center text-xs text-zinc-400">© {new Date().getFullYear()} ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশন। সর্বস্বত্ব সংরক্ষিত।</div>
  </footer>;
}
