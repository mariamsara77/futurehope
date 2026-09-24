"use client";

import Image from "next/image";
import { useState } from "react";

type WorkImage = {
  id: number | string;
  url: string;
  thumb_url?: string | null;
  name?: string | null;
};

export default function WorkGallery({
  images,
  title,
  coverUrl,
}: {
  images?: WorkImage[];
  title: string;
  coverUrl?: string | null;
}) {
  const items = (images?.length ? images : coverUrl ? [{ id: "cover", url: coverUrl, thumb_url: coverUrl }] : []);
  const [index, setIndex] = useState(0);

  if (!items.length) {
    return (
      <div className="flex h-72 items-center justify-center bg-emerald-50 text-sm font-semibold text-emerald-700 sm:h-96">
        {title}
      </div>
    );
  }

  const current = items[index];

  return (
    <div className="relative bg-zinc-950">
      <div className="relative aspect-[16/10] w-full sm:aspect-[16/9]">
        <Image
          src={current.url}
          alt={title}
          fill
          priority={index === 0}
          sizes="(max-width: 640px) 100vw, 1000px"
          className="object-cover"
        />
      </div>

      {items.length > 1 && (
        <>
          <button
            type="button"
            aria-label="আগের ছবি"
            onClick={() => setIndex((value) => (value - 1 + items.length) % items.length)}
            className="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-black/55 px-4 py-3 text-xl font-bold text-white backdrop-blur transition hover:bg-black/75"
          >
            ‹
          </button>
          <button
            type="button"
            aria-label="পরের ছবি"
            onClick={() => setIndex((value) => (value + 1) % items.length)}
            className="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-black/55 px-4 py-3 text-xl font-bold text-white backdrop-blur transition hover:bg-black/75"
          >
            ›
          </button>

          <div className="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-1.5 rounded-full bg-black/45 px-3 py-2 backdrop-blur">
            {items.map((item, itemIndex) => (
              <button
                key={item.id}
                type="button"
                aria-label={"ছবি " + (itemIndex + 1)}
                onClick={() => setIndex(itemIndex)}
                className={"h-2 rounded-full transition-all " + (itemIndex === index ? "w-6 bg-white" : "w-2 bg-white/55")}
              />
            ))}
          </div>
        </>
      )}

      {items.length > 1 && (
        <div className="flex gap-2 overflow-x-auto bg-white/95 p-3">
          {items.map((item, itemIndex) => (
            <button
              key={item.id}
              type="button"
              onClick={() => setIndex(itemIndex)}
              className={"relative h-16 w-24 shrink-0 overflow-hidden rounded-xl ring-2 transition " + (itemIndex === index ? "ring-emerald-500" : "ring-transparent")}
            >
              <Image
                src={item.thumb_url || item.url}
                alt=""
                fill
                sizes="96px"
                className="object-cover"
              />
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
