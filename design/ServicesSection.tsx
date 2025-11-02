import React from "react";
import x121221 from "./12122-1.png";
import rectangle from "./rectangle.png";

export const ServicesSection = (): JSX.Element => {
  const navigationItems = [
    { text: "الرئيسية", left: "67.31%" },
    { text: "خدماتنا", left: "59.51%" },
    { text: "عن الشركة", left: "48.07%" },
    { text: "تواصل معنا", left: "34.78%" },
  ];

  return (
    <section className="absolute -top-2 -left-0.5 w-[1441px] h-[1024px] bg-white rounded-[0px_0px_100px_100px] overflow-hidden">
      <img
        className="absolute top-2 left-0.5 w-[1439px] h-[1016px] aspect-[1.41] object-cover"
        alt="خلفية قسم الخدمات"
        src={x121221}
      />

      <header className="absolute w-[89.87%] h-[7.52%] top-[5.86%] left-[6.38%]">
        <nav aria-label="التنقل الرئيسي">
          {navigationItems.map((item, index) => (
            <a
              key={index}
              href="#"
              className="absolute w-auto h-[61.04%] top-[19.48%] [font-family:'Cairo-Bold',Helvetica] font-bold text-[#104d88] text-[25px] text-center tracking-[0] leading-[normal] [direction:rtl]"
              style={{ left: item.left }}
            >
              {item.text}
            </a>
          ))}
        </nav>

        <button className="absolute top-[9px] left-0 w-[205px] h-[60px] bg-[#104d88] rounded-[10px]">
          <span className="absolute top-4 left-4 [font-family:'Cairo-Bold',Helvetica] font-bold text-neutral-100 text-[25px] text-left tracking-[0] leading-[normal] [direction:rtl]">
            اطلب عرض سعر
          </span>
        </button>

        <img
          className="absolute w-[16.85%] h-full top-0 left-[82.38%]"
          alt="شعار الشركة"
          src={rectangle}
        />
      </header>

      <h1 className="absolute top-[286px] left-[calc(50.00%_-_180px)] w-[936px] [font-family:'Cairo-Bold',Helvetica] font-bold text-transparent text-8xl text-center tracking-[0] leading-[90px] [direction:rtl]">
        <span className="text-[#1c75bc]">عزمنا</span>
        <span className="text-[#104d88]">&nbsp;</span>
        <span className="text-[#104d88] text-[64px]">
          في التوريد
          <br />
          أسـاس كـل إنجــــاز
        </span>
      </h1>

      <p className="absolute top-[calc(50.00%_+_2px)] left-[calc(50.00%_+_8px)] w-[540px] [font-family:'Cairo-Regular',Helvetica] font-normal text-[#3172b4] text-[25px] tracking-[0] leading-[35px] [direction:rtl]">
        من توريد مواد البناء إلى خدمات التصميم والبيع بالأجل، تجمع منصة عزم
        الإنجاز كل ما تحتاجه لإنجاز مشروعك بسهولة وجودة عالية في مكان واحد.
      </p>

      <div className="absolute top-[680px] left-[732px] flex gap-4">
        <button className="w-[252px] h-[60px] bg-[#3172b4] rounded-[10px] shadow-[0px_2px_2px_#00000040]">
          <span className="block pt-[10px] [font-family:'Cairo-Regular',Helvetica] font-normal text-neutral-100 text-2xl text-center tracking-[0] leading-[normal] [direction:rtl]">
            اطلب خدمة البيع بالأجل
          </span>
        </button>

        <button className="w-[234px] h-16 bg-white rounded-[10px] border-2 border-solid border-[#3172b4] shadow-[0px_2px_2px_#3172b4]">
          <span className="block pt-[10px] [font-family:'Cairo-Medium',Helvetica] font-medium text-[#104d88] text-2xl text-center tracking-[0] leading-[normal] [direction:rtl]">
            تصفح منتجات البناء
          </span>
        </button>
      </div>
    </section>
  );
};
