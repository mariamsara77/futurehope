import type { Metadata } from "next";
import { Hind_Siliguri } from "next/font/google";
import "./globals.css";
import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";

const hindSiliguri = Hind_Siliguri({
  weight: ["300", "400", "500", "600", "700"],
  subsets: ["bengali"],
});

export const metadata: Metadata = {
  title: "আমাদের ফাউন্ডেশন | একতাবদ্ধ গ্রাম, সুন্দর ভবিষ্যৎ",
  description: "গ্রামের উন্নয়নে নিবেদিত প্রাণ",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="bn" className="scroll-smooth">
      <body
        className={`${hindSiliguri.className} bg-gray-50 text-gray-800 antialiased selection:bg-primary-500 selection:text-white flex flex-col min-h-screen`}
      >
        <Navbar />
        <main className="flex-grow">{children}</main>
        <Footer />
      </body>
    </html>
  );
}
