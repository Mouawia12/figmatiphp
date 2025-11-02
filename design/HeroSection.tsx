import React from "react";
import x222 from "./2-2-2.svg";
import x223 from "./2-2-3.svg";
import x224 from "./2-2-4.svg";
import x22 from "./2-2.svg";
import serviceButtonBackground from "./service-button-background.png";
import serviceCardBackground from "./service-card-background.png";

interface ServiceCard {
  id: number;
  icon: string;
  title: string;
  description: string;
  buttonText: string;
  hasBackgroundImage: boolean;
  iconStyles: string;
  titleStyles: string;
}

export const HeroSection = (): JSX.Element => {
  const serviceCards: ServiceCard[] = [
    {
      id: 1,
      icon: x22,
      title: "طلبات عرض السعر",
      description:
        "اطلب عرض السعر الآن واسمح لنا بتقديم أفضل الحلول والمنتجات التي تناسب مشروعك وميزانيتك.",
      buttonText: "اطلب عرض سعر",
      hasBackgroundImage: true,
      iconStyles: "top-[22px] left-[470px] w-[98px] h-[98px]",
      titleStyles: "left-[337px] whitespace-nowrap",
    },
    {
      id: 2,
      icon: x222,
      title: "البيع بالأجل",
      description:
        "اشتر الآن وادفع لاحقا بخطط ميسرة وسهلة، لتبدأ مشروعك بدون ضغط مالي.",
      buttonText: "طلب الخدمة",
      hasBackgroundImage: false,
      iconStyles: "top-3.5 left-[470px] w-[113px] h-[113px]",
      titleStyles: "left-[423px] whitespace-nowrap",
    },
    {
      id: 3,
      icon: x223,
      title: "3.  التصميم الداخلي",
      description:
        "صمم مساحتك بخبرة مهندسين محترفين، مع تسعير واقعي للتوريد والتنفيذ.",
      buttonText: "ابدأ التصميم",
      hasBackgroundImage: false,
      iconStyles: "top-[18px] left-[477px] w-[105px] h-[105px]",
      titleStyles: "left-[323px] w-[238px]",
    },
    {
      id: 4,
      icon: x224,
      title: "4. المتجر الإلكتروني",
      description:
        "تسوق منتجات البناء والتشطيب مباشرة من المتجر الإلكتروني الخاص بنا.",
      buttonText: "تسوق الان",
      hasBackgroundImage: false,
      iconStyles: "top-[18px] left-[460px] w-[105px] h-[105px]",
      titleStyles: "left-[321px] whitespace-nowrap",
    },
  ];

  const getCardPosition = (index: number): string => {
    const positions = [
      "top-[3px] left-[748px]",
      "top-[3px] left-[52px]",
      "top-[492px] left-[748px]",
      "top-[492px] left-[52px]",
    ];
    return positions[index];
  };

  const getButtonLeftPosition = (index: number): string => {
    const positions = [
      "left-[392px]",
      "left-[407px]",
      "left-[411px]",
      "left-[417px]",
    ];
    return positions[index];
  };

  const getDescriptionLeftPosition = (index: number): string => {
    return index === 1 || index === 3 ? "left-[71px]" : "left-[83px]";
  };

  const getDescriptionWidth = (index: number): string => {
    return index === 1 || index === 3 ? "w-[490px]" : "w-[478px]";
  };

  return (
    <section className="absolute top-[1662px] -left-1.5 w-[1442px] h-[1179px]">
      <div
        className="absolute top-12 left-[1163px] w-[142px] h-1.5 bg-[#3172b4] opacity-50"
        aria-hidden="true"
      />

      <header className="absolute top-0 left-[725px] w-[580px]">
        <h2 className="[font-family:'Cairo-Bold',Helvetica] font-bold text-transparent text-5xl tracking-[0] leading-[60px] [direction:rtl]">
          <span className="text-[#104d88]">
            خدمات
            <br />
          </span>
          <span className="text-[#1c75bc] text-[35px]">
            البناء في مكان واحد
          </span>
        </h2>
      </header>

      <div className="absolute top-[202px] left-[calc(50.00%_-_721px)] w-[1440px] h-[977px] overflow-hidden">
        {serviceCards.map((card, index) => (
          <article
            key={card.id}
            className={`absolute ${getCardPosition(index)} w-[647px] h-[427px]`}
          >
            {card.hasBackgroundImage ? (
              <img
                className="absolute top-[-3px] left-[-13px] w-[667px] h-[447px]"
                alt=""
                src={serviceCardBackground}
                aria-hidden="true"
              />
            ) : (
              <div className="absolute -top-px -left-px w-[643px] h-[429px] bg-white rounded-[50px] border border-solid border-[#1c75bc2e] shadow-[0px_4px_9px_3px_#0000001a]" />
            )}

            <img
              className={`${card.iconStyles} absolute aspect-[1] object-cover`}
              alt=""
              src={card.icon}
              aria-hidden="true"
            />

            {card.hasBackgroundImage ? (
              <img
                className="absolute top-[331px] left-[373px] w-[194px] h-[54px]"
                alt=""
                src={serviceButtonBackground}
                aria-hidden="true"
              />
            ) : (
              <div className="absolute top-[331px] left-[375px] w-[190px] h-[50px] bg-[#3172b4] rounded-[10px] shadow-[0px_2px_2px_#00000040]" />
            )}

            <button
              className={`absolute top-[333px] ${getButtonLeftPosition(index)} [font-family:'Cairo-Regular',Helvetica] font-normal text-neutral-100 text-2xl text-left tracking-[0] leading-[normal] [direction:rtl] bg-transparent border-0 cursor-pointer`}
              aria-label={card.buttonText}
            >
              {card.buttonText}
            </button>

            <h3
              className={`absolute top-[133px] ${card.titleStyles} [font-family:'Cairo-SemiBold',Helvetica] font-semibold text-[#104d88] text-3xl tracking-[0] leading-10 [direction:rtl]`}
            >
              {card.title}
            </h3>

            <p
              className={`absolute top-[189px] ${getDescriptionLeftPosition(index)} ${getDescriptionWidth(index)} [font-family:'Cairo-Regular',Helvetica] font-normal text-[#1c75bc] text-[25px] text-justify tracking-[0] leading-10 [direction:rtl]`}
            >
              {card.description}
            </p>
          </article>
        ))}

        <div
          className="absolute top-[971px] left-1.5 w-[1453px] h-[1226px] bg-[#d9d9d9]"
          aria-hidden="true"
        />
      </div>
    </section>
  );
};
