import React from "react";
import rectangle38 from "./rectangle-38.svg";

export const InfoSection = (): JSX.Element => {
  const features = [
    {
      id: "01",
      title: "خبرة في توريد مواد البناء",
      top: "196px",
      left: "1112px",
      textTop: "299px",
      textLeft: "826px",
      textWhitespace: "whitespace-nowrap",
    },
    {
      id: "02",
      title: "تكامل بين التصميم،\nالتوريد، والبيع بالأجل",
      top: "196px",
      left: "739px",
      textTop: "299px",
      textLeft: "487px",
      textWhitespace: "",
    },
    {
      id: "03",
      title: "تسهيلات مرنة سهلة",
      top: "196px",
      left: "409px",
      textTop: "299px",
      textLeft: "126px",
      textWhitespace: "",
    },
    {
      id: "04",
      title: "دعم فوري عبر الشات\nبوت الذكي",
      top: "447px",
      left: "409px",
      textTop: "544px",
      textLeft: "487px",
      textWhitespace: "",
    },
    {
      id: "05",
      title: "سرعة في التوصيل\nودقة في المواعيد",
      top: "447px",
      left: "739px",
      textTop: "544px",
      textLeft: "164px",
      textWhitespace: "",
    },
  ];

  return (
    <section
      className="absolute top-[3129px] left-[135px] w-[1196px] h-[638px]"
      aria-labelledby="info-section-heading"
    >
      <h2
        id="info-section-heading"
        className="absolute top-0 left-[604px] w-[580px] [font-family:'Cairo-Bold',Helvetica] font-bold text-transparent text-5xl tracking-[0] leading-[60px] [direction:rtl]"
      >
        <span className="text-[#104d88]">
          لماذا تختار عزم الإنجاز ؟<br />
        </span>

        <span className="text-[#1c75bc] text-[35px]">ليه تختارنا ؟</span>
      </h2>

      <img
        className="absolute top-[225px] -left-px w-[1139px] h-[253px]"
        alt=""
        src={rectangle38}
        role="presentation"
      />

      {features.map((feature) => (
        <React.Fragment key={feature.id}>
          <div
            className="absolute"
            style={{
              top: feature.top,
              left: feature.left,
              width: "42px",
              height: "60px",
            }}
            aria-label={`رقم ${feature.id}`}
          >
            <div className="absolute top-2.5 left-0 w-10 h-10 bg-[#1c75bc] rounded-[20px]" />
            <div className="absolute top-0 left-2 [font-family:'Cairo-Bold',Helvetica] font-bold text-white text-xl text-right tracking-[0] leading-[60px] whitespace-nowrap">
              {feature.id}
            </div>
          </div>

          {feature.id === "01" ? (
            <p
              className="absolute [font-family:'Cairo-Regular',Helvetica] font-normal text-[#1c75bc] text-[25px] text-justify tracking-[0] leading-10 whitespace-nowrap [direction:rtl]"
              style={{ top: feature.textTop, left: feature.textLeft }}
            >
              {feature.title}
            </p>
          ) : feature.id === "03" ? (
            <div
              className="absolute [font-family:'Cairo-Medium',Helvetica] font-medium text-[#1c75bc] text-[25px] text-justify tracking-[0] leading-[normal] [direction:rtl]"
              style={{ top: feature.textTop, left: feature.textLeft }}
            >
              {feature.title}
            </div>
          ) : (
            <p
              className="absolute [font-family:'Cairo-Medium',Helvetica] font-medium text-[#1c75bc] text-[25px] text-justify tracking-[0] leading-[normal] [direction:rtl]"
              style={{ top: feature.textTop, left: feature.textLeft }}
            >
              {feature.title.split("\n").map((line, index) => (
                <React.Fragment key={index}>
                  {line}
                  {index < feature.title.split("\n").length - 1 && <br />}
                </React.Fragment>
              ))}
            </p>
          )}
        </React.Fragment>
      ))}
    </section>
  );
};
