import React from "react";
import image from "./image.png";

export const FooterSection = (): JSX.Element => {
  const companyInfo = [
    { label: "اسم الشركة: شركة عزم الإنجاز للأدوات الصحية (مساهمة مبسطة)" },
    { label: "الرقم الموحد: 7015661239" },
    {
      label: "رقم الهاتف / واتساب: ",
      link: "tel:0115186956",
      linkText: "0115186956",
    },
    { label: "العنوان: برج سنام، طريق الملك سعود المعذر، الرياض 12624" },
  ];

  const socialLinks = [
    { href: "https://instagram.com/Azemalenjaz_sa", text: "إنستغرام" },
    { href: "https://www.tiktok.com/@Azemalenjaz_sa", text: "تيك توك" },
    { href: "https://www.snapchat.com/add/Azemalenjaz_sa", text: "سناب شات" },
    { href: "https://x.com/Azemalenjaz_sa", text: "إكس" },
    {
      href: "https://www.linkedin.com/company/azem-alenjaz-company/",
      text: "لنكد إن",
    },
  ];

  const navigationLinks = [
    { href: "https://azmalenjaz.com/crosing/login.php", text: "تسجيل الدخول" },
    { href: "https://azmalenjaz.com/crosing/register.php", text: "إنشاء حساب" },
    {
      href: "https://azmalenjaz.com/crosing/api_docs.php",
      text: "وثائق الـ API",
    },
    {
      href: "https://azmalenjaz.com/crosing/dashboard.php",
      text: "لوحة التحكم",
    },
  ];

  return (
    <footer className="absolute top-[5686px] -left-1.5 w-[1448px] h-[350px] bg-transparent rounded-[50px_50px_0px_0px] overflow-hidden bg-[linear-gradient(180deg,rgba(255,255,255,1)_0%,rgba(215,225,235,1)_60%,rgba(186,204,221,1)_84%,rgba(149,176,202,1)_100%)]">
      <div className="absolute w-[39.31%] h-[52.30%] top-[35.14%] left-[52.43%] [font-family:'Cairo-SemiBold',Helvetica] font-semibold text-[#1c75bc] text-xl tracking-[0] leading-[normal] [direction:rtl]">
        {companyInfo.map((item, index) => (
          <p
            key={index}
            className="[font-family:'Cairo-SemiBold',Helvetica] font-semibold text-[#1c75bc] text-xl tracking-[0]"
          >
            {item.link ? (
              <>
                {item.label}
                <a
                  href={item.link}
                  rel="noopener noreferrer"
                  target="_blank"
                  className="underline"
                >
                  {item.linkText}
                </a>
                <br />
              </>
            ) : (
              <>
                {item.label}
                <br />
              </>
            )}
          </p>
        ))}
      </div>

      <nav
        className="w-[8.26%] h-[65.37%] top-[32.86%] left-[36.04%] [font-family:'Cairo-SemiBold',Helvetica] font-semibold text-[#1c75bc] text-xl underline absolute tracking-[0] leading-[normal] [direction:rtl]"
        aria-label="Social media links"
      >
        {socialLinks.map((link, index) => (
          <React.Fragment key={index}>
            <a
              href={link.href}
              rel="noopener noreferrer"
              target="_blank"
              className="[font-family:'Cairo-SemiBold',Helvetica] font-semibold text-[#1c75bc] text-xl tracking-[0] underline"
            >
              {link.text}
            </a>
            <br />
          </React.Fragment>
        ))}
      </nav>

      <nav
        className="absolute w-[10.42%] h-[52.30%] top-[32.80%] left-[10.35%] [font-family:'Cairo-SemiBold',Helvetica] font-semibold text-[#1c75bc] text-xl tracking-[0] leading-[normal] underline [direction:rtl]"
        aria-label="Navigation links"
      >
        {navigationLinks.map((link, index) => (
          <React.Fragment key={index}>
            <a
              href={link.href}
              rel="noopener noreferrer"
              target="_blank"
              className="[font-family:'Cairo-SemiBold',Helvetica] font-semibold text-[#1c75bc] text-xl tracking-[0] underline"
            >
              {link.text}
            </a>
            <br />
          </React.Fragment>
        ))}
      </nav>

      <h3 className="absolute w-[4.17%] h-[16.61%] top-[16.29%] left-[15.56%] [font-family:'Cairo-Bold',Helvetica] font-bold text-[#104d88] text-[25px] tracking-[0] leading-[normal] [direction:rtl]">
        روابط
      </h3>

      <h3 className="w-[12.43%] h-[16.61%] top-[17.28%] left-[31.87%] [font-family:'Cairo-Bold',Helvetica] font-bold text-[#104d88] text-[25px] absolute tracking-[0] leading-[normal] [direction:rtl]">
        حسابات التواصل
      </h3>

      <img
        className="absolute w-[14.86%] h-[21.71%] top-[11.14%] left-[74.79%]"
        alt="Company logo"
        src={image}
      />
    </footer>
  );
};
