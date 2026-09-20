import Link from "next/link";

export default function Footer() {
  return (
    <footer id="contact" className="bg-gray-900 text-white pt-16 pb-8 mt-auto">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-12 mb-12">
          <div>
            <div className="flex items-center gap-2 mb-6">
              <img src="/500.png" alt="Logo" className="size-14" />
              <span className="font-bold text-xl tracking-tight max-w-50">
                ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশন
              </span>
            </div>
            <p className="text-gray-400 leading-relaxed mb-6">
              গ্রামের মানুষের পাশে দাঁড়ানো এবং সমাজের পিছিয়ে পড়া মানুষের
              অধিকার আদায়ে আমরা প্রতিশ্রুতিবদ্ধ। আসুন সবাই মিলে একটি সুন্দর
              সমাজ গড়ি।
            </p>
          </div>

          <div>
            <h4 className="text-lg font-bold mb-6 border-b border-gray-700 pb-2 inline-block">
              প্রয়োজনীয় লিংক
            </h4>
            <ul className="space-y-3">
              <li>
                <Link
                  href="#home"
                  className="text-gray-400 hover:text-primary-400 transition-colors flex items-center gap-2"
                >
                  <span className="text-primary-500">▶</span> হোম
                </Link>
              </li>
              <li>
                <Link
                  href="#aim"
                  className="text-gray-400 hover:text-primary-400 transition-colors flex items-center gap-2"
                >
                  <span className="text-primary-500">▶</span> আমাদের লক্ষ্য
                </Link>
              </li>
              <li>
                <Link
                  href="#activities"
                  className="text-gray-400 hover:text-primary-400 transition-colors flex items-center gap-2"
                >
                  <span className="text-primary-500">▶</span> কার্যক্রম
                </Link>
              </li>
              <li>
                <Link
                  href="#members"
                  className="text-gray-400 hover:text-primary-400 transition-colors flex items-center gap-2"
                >
                  <span className="text-primary-500">▶</span> সদস্য তালিকা
                </Link>
              </li>
            </ul>
          </div>

          <div>
            <h4 className="text-lg font-bold mb-6 border-b border-gray-700 pb-2 inline-block">
              যোগাযোগ
            </h4>
            <ul className="space-y-4 text-gray-400">
              <li className="flex items-start gap-3">
                <svg
                  className="w-6 h-6 text-primary-500 flex-shrink-0"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                  ></path>
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                  ></path>
                </svg>
                <span>
                  গ্রাম: ছোনবুনিয়া, ঘুটাবাছা, গোলবুনিয়া, পোস্ট: কালমেঘা, <br />{" "}
                  উপজেলা: পাথরঘাটা, জেলা: বরগুনা
                </span>
              </li>
              <li className="flex items-center gap-3">
                <svg
                  className="w-5 h-5 text-primary-500 flex-shrink-0"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                  ></path>
                </svg>
                <span>০১৩৪০-৭৯২৬৭৭</span>
              </li>
            </ul>
          </div>
        </div>

        <div className="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center text-gray-500 text-sm">
          <p>&copy; ২০২৬ আমাদের ফাউন্ডেশন। সর্বস্বত্ব সংরক্ষিত।</p>
        </div>
      </div>
    </footer>
  );
}
