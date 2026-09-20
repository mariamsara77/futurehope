import Link from "next/link";

export default function Home() {
  return (
    <>
      {/* Hero Section */}
      <section id="home" className="relative pt-20 h-screen">
        <div className="absolute inset-0 z-0">
          <img
            src="/village.jpg"
            alt="Village Background"
            className="w-full h-full object-cover"
          />
          <div className="absolute inset-0 bg-gray-600/50 mix-blend-multiply"></div>
        </div>

        <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-150 lg:h-175 flex flex-col justify-center items-center text-center">
          <span className="px-4 py-1.5 rounded-full bg-primary-500/20 text-primary-100 font-semibold mb-6 border border-primary-400/30 backdrop-blur-sm">
            সততা ও বিশ্বাসের সাথে
          </span>
          <h1 className="text-4xl sm:text-5xl lg:text-7xl font-bold text-white mb-6 leading-tight drop-shadow-lg">
            একসাথে একটি সুন্দর <br />{" "}
            <span className="text-primary-400">আগামী জন্য</span>
          </h1>
          <p className="mt-4 text-xl sm:text-2xl text-gray-200 max-w-3xl mb-10 drop-shadow-md leading-relaxed">
            গ্রামের মানুষের জীবনমান উন্নয়ন, শিক্ষা প্রসার ও পারস্পরিক সহযোগিতার
            মাধ্যমে একটি আদর্শ সমাজ গড়ার প্রত্যয়ে আমরা কাজ করে যাচ্ছি।
          </p>
          <div className="flex flex-col sm:flex-row gap-4">
            <Link
              href="#activities"
              className="bg-primary-600 hover:bg-primary-500 text-white px-8 py-4 rounded-full text-lg font-semibold transition-all shadow-lg hover:shadow-primary-500/30 transform hover:-translate-y-1"
            >
              আমাদের কাজ দেখুন
            </Link>
            <Link
              href="#contact"
              className="bg-white/10 hover:bg-white/20 text-white backdrop-blur-md border border-white/30 px-8 py-4 rounded-full text-lg font-semibold transition-all shadow-lg transform hover:-translate-y-1"
            >
              যোগাযোগ করুন
            </Link>
          </div>
        </div>
      </section>

      {/* Our Aims Section */}
      <section id="aim" className="py-20 bg-white h-screen">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4 relative inline-block">
              আমাদের লক্ষ্য ও উদ্দেশ্য
              <span className="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-20 h-1 bg-primary-500 rounded"></span>
            </h2>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {/* Aim 1 */}
            <div className="bg-primary-50 rounded-2xl p-8 border border-primary-100 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 group">
              <h3 className="text-2xl font-bold text-gray-900 mb-4">
                শিক্ষা উন্নয়ন
              </h3>
              <p className="text-gray-600 leading-relaxed">
                গ্রামের প্রতিটি শিশুর জন্য মানসম্মত শিক্ষা নিশ্চিত করা এবং
                দরিদ্র মেধাবী শিক্ষার্থীদের বৃত্তি প্রদান করা।
              </p>
            </div>
            {/* Aim 2 */}
            <div className="bg-primary-50 rounded-2xl p-8 border border-primary-100 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 group">
              <h3 className="text-2xl font-bold text-gray-900 mb-4">
                স্বাস্থ্যসেবা
              </h3>
              <p className="text-gray-600 leading-relaxed">
                বিনা মূল্যে চিকিৎসা ক্যাম্পের আয়োজন করা, জরুরি রক্তের ব্যবস্থা
                করা এবং সচেতনতা বৃদ্ধি করা।
              </p>
            </div>
            {/* Aim 3 */}
            <div className="bg-primary-50 rounded-2xl p-8 border border-primary-100 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 group">
              <h3 className="text-2xl font-bold text-gray-900 mb-4">
                দারিদ্র্য বিমোচন
              </h3>
              <p className="text-gray-600 leading-relaxed">
                অসহায় মানুষদের আত্মকর্মসংস্থানের জন্য আর্থিক সহযোগিতা করা,
                শীতবস্ত্র ও খাদ্য সামগ্রী বিতরণ।
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Activities Section */}
      <section id="activities" className="py-20 bg-gray-50 h-screen">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="mb-12">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4 relative inline-block">
              সাম্প্রতিক কার্যক্রম
              <span className="absolute -bottom-2 left-0 w-16 h-1 bg-primary-500 rounded"></span>
            </h2>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div className="bg-white rounded-2xl overflow-hidden shadow-md">
              <img
                src="https://placehold.co/600x400/22c55e/ffffff?text=Winter+Clothes"
                alt="Work"
                className="w-full h-56 object-cover"
              />
              <div className="p-6">
                <h3 className="text-xl font-bold text-gray-900 mb-3">
                  অসহায়দের মাঝে শীতবস্ত্র বিতরণ
                </h3>
                <p className="text-gray-600 mb-4">
                  তীব্র শীতে গ্রামের অসহায় মানুষের জন্য কম্বল বিতরণ।
                </p>
              </div>
            </div>
            <div className="bg-white rounded-2xl overflow-hidden shadow-md">
              <img
                src="https://placehold.co/600x400/16a34a/ffffff?text=Medical+Camp"
                alt="Medical"
                className="w-full h-56 object-cover"
              />
              <div className="p-6">
                <h3 className="text-xl font-bold text-gray-900 mb-3">
                  বিনামূল্যে চিকিৎসা
                </h3>
                <p className="text-gray-600 mb-4">
                  ফ্রী মেডিকেল ক্যাম্পের আয়োজন ও ওষুধ প্রদান।
                </p>
              </div>
            </div>
            <div className="bg-white rounded-2xl overflow-hidden shadow-md">
              <img
                src="https://placehold.co/600x400/15803d/ffffff?text=Tree+Plantation"
                alt="Tree"
                className="w-full h-56 object-cover"
              />
              <div className="p-6">
                <h3 className="text-xl font-bold text-gray-900 mb-3">
                  বৃক্ষরোপণ কর্মসূচি
                </h3>
                <p className="text-gray-600 mb-4">
                  গ্রামের প্রধান সড়কের দুই পাশে গাছের চারা রোপণ।
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Committee Section */}
      <section id="committee" className="py-20 bg-white h-screen">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4 relative inline-block">
              কার্যকরী কমিটি
              <span className="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-20 h-1 bg-primary-500 rounded"></span>
            </h2>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10">
            <div className="bg-gray-50 rounded-2xl p-8 text-center border border-gray-100 hover:shadow-lg transition-shadow">
              <img
                src="https://placehold.co/200x200/15803d/ffffff?text=Image"
                alt="President"
                className="w-32 h-32 mx-auto mb-6 object-cover rounded-full border-4 border-white shadow-md"
              />
              <h3 className="text-2xl font-bold text-gray-900 mb-1">
                মো. আব্দুর রহমান
              </h3>
              <p className="text-primary-600 font-semibold">সভাপতি</p>
            </div>
            <div className="bg-gray-50 rounded-2xl p-8 text-center border border-gray-100 hover:shadow-lg transition-shadow">
              <img
                src="https://placehold.co/200x200/16a34a/ffffff?text=Image"
                alt="Secretary"
                className="w-32 h-32 mx-auto mb-6 object-cover rounded-full border-4 border-white shadow-md"
              />
              <h3 className="text-2xl font-bold text-gray-900 mb-1">
                শফিকুল ইসলাম
              </h3>
              <p className="text-primary-600 font-semibold">সাধারণ সম্পাদক</p>
            </div>
            <div className="bg-gray-50 rounded-2xl p-8 text-center border border-gray-100 hover:shadow-lg transition-shadow">
              <img
                src="https://placehold.co/200x200/22c55e/ffffff?text=Image"
                alt="Treasurer"
                className="w-32 h-32 mx-auto mb-6 object-cover rounded-full border-4 border-white shadow-md"
              />
              <h3 className="text-2xl font-bold text-gray-900 mb-1">
                রফিকুল হাসান
              </h3>
              <p className="text-primary-600 font-semibold">কোষাধ্যক্ষ</p>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
