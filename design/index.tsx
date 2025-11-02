import React from "react";
import x12 from "./1-2.png";
import x1 from "./1.png";
import { FaqSection } from "./FaqSection";
import { FooterSection } from "./FooterSection";
import { HeroSection } from "./HeroSection";
import { InfoSection } from "./InfoSection";
import { ServicesSection } from "./ServicesSection";
import aboutUsBackground from "./about-us-background.png";
import aboutUsDescription from "./about-us-description.png";
import ellipse1 from "./ellipse-1.svg";
import ellipse2 from "./ellipse-2.svg";
import image2 from "./image-2.svg";
import image from "./image.svg";

export const Desktop = (): JSX.Element => {
  return (
    <div className="relative w-[1440px] h-[6036px] bg-white overflow-hidden">
      <img
        className="absolute top-[4047px] left-0 w-[1440px] h-[1204px] aspect-[1.38] object-cover"
        alt="Element"
        src={x12}
      />

      <ServicesSection />
      <HeroSection />

      <section
        className="absolute top-[839px] left-[calc(50.00%_-_602px)] w-[1203px] h-[596px]"
        aria-label="About Us Section"
      >
        <img
          className="absolute top-[-9px] left-[calc(50.00%_-_612px)] w-[1225px] h-[618px]"
          alt=""
          src={aboutUsBackground}
          role="presentation"
        />

        <div
          className="absolute top-36 left-[777px] w-[308px] h-[308px]"
          aria-hidden="true"
        >
          <img
            className="absolute top-8 left-[18px] w-[272px] h-[272px] object-cover"
            alt=""
            src={ellipse2}
            role="presentation"
          />

          <img
            className="absolute top-0 left-[74px] w-[234px] h-[281px]"
            alt=""
            src={ellipse1}
            role="presentation"
          />
        </div>

        <div className="absolute top-[132px] left-[117px] w-[522px] h-[331px]">
          <img
            className="absolute top-[124px] left-px w-[515px] h-[206px]"
            alt="About us description"
            src={aboutUsDescription}
          />

          <h2 className="absolute top-0 left-[360px] w-[158px] [font-family:'Cairo-Bold',Helvetica] font-bold text-[#104d88] text-5xl tracking-[0] leading-[normal] [direction:rtl]">
            من نحن
          </h2>

          <img
            className="top-[301px] left-[115px] absolute w-10 h-[30px]"
            alt=""
            src={image}
            role="presentation"
          />

          <img
            className="top-28 left-[467px] absolute w-10 h-[30px]"
            alt=""
            src={image2}
            role="presentation"
          />
        </div>
      </section>

      <FaqSection />
      <FooterSection />

      <img
        className="absolute top-[2792px] left-[calc(50.00%_-_720px)] w-[1440px] h-[1280px] aspect-[1.41] object-cover"
        alt="Element"
        src={x1}
      />

      <InfoSection />
    </div>
  );
};
