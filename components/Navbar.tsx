"use client";
import { useState } from "react";
import Link from "next/link";

export default function Navbar() {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <header className="fixed w-full top-0 z-50 bg-white/50 backdrop-blur-md shadow-sm">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-18">
          <div
            className="shrink-0 flex items-center gap-2 cursor-pointer"
            onClick={() => window.scrollTo(0, 0)}
          >
            <img src="/500.png" alt="Logo" className="size-14" />
            <span className="font-bold text-xl text-green-800 tracking-tight max-w-50 ">
              ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশন
            </span>
          </div>

          <nav className="hidden md:flex space-x-8">
            <Link
              href="#home"
              className="text-gray-600 hover:text-green-600 font-medium transition-colors"
            >
              হোম
            </Link>
            <Link
              href="#aim"
              className="text-gray-600 hover:text-green-600 font-medium transition-colors"
            >
              আমাদের লক্ষ্য
            </Link>
            <Link
              href="#activities"
              className="text-gray-600 hover:text-green-600 font-medium transition-colors"
            >
              কার্যক্রম
            </Link>
            <Link
              href="#committee"
              className="text-gray-600 hover:text-green-600 font-medium transition-colors"
            >
              কমিটি
            </Link>
            <Link
              href="#contact"
              className="text-gray-600 hover:text-green-600 font-medium transition-colors"
            >
              যোগাযোগ
            </Link>
          </nav>

          <div className="hidden md:flex">
            <Link
              href="#members"
              className="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-full font-semibold transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
            >
              সদস্য তালিকা
            </Link>
          </div>

          <div className="md:hidden flex items-center">
            <button
              onClick={() => setIsOpen(!isOpen)}
              className="text-gray-600 hover:text-green-600 focus:outline-none"
            >
              <svg
                className="h-8 w-8"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2"
                  d={
                    isOpen ? "M6 18L18 6M6 6l12 12" : "M4 6h16M4 12h16M4 18h16"
                  }
                />
              </svg>
            </button>
          </div>
        </div>
      </div>

      {isOpen && (
        <div className="md:hidden bg-white border-t border-gray-100 shadow-lg absolute w-full">
          <div className="px-4 pt-2 pb-6 space-y-2">
            <Link
              href="#home"
              onClick={() => setIsOpen(false)}
              className="block px-3 py-2 text-base font-medium text-gray-700 hover:text-green-600 hover:bg-green-50 rounded-md"
            >
              হোম
            </Link>
            <Link
              href="#aim"
              onClick={() => setIsOpen(false)}
              className="block px-3 py-2 text-base font-medium text-gray-700 hover:text-green-600 hover:bg-green-50 rounded-md"
            >
              আমাদের লক্ষ্য
            </Link>
            <Link
              href="#activities"
              onClick={() => setIsOpen(false)}
              className="block px-3 py-2 text-base font-medium text-gray-700 hover:text-green-600 hover:bg-green-50 rounded-md"
            >
              কার্যক্রম
            </Link>
            <Link
              href="#committee"
              onClick={() => setIsOpen(false)}
              className="block px-3 py-2 text-base font-medium text-gray-700 hover:text-green-600 hover:bg-green-50 rounded-md"
            >
              কমিটি
            </Link>
            <Link
              href="#contact"
              onClick={() => setIsOpen(false)}
              className="block px-3 py-2 text-base font-medium text-gray-700 hover:text-green-600 hover:bg-green-50 rounded-md"
            >
              যোগাযোগ
            </Link>
          </div>
        </div>
      )}
    </header>
  );
}
