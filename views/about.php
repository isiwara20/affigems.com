<?php
require_once __DIR__ . '/../config/app.php';
$pageTitle = "About Us - " . SITE_NAME;
require_once __DIR__ . '/partials/header.php';

$aboutBannerUrl = null;
$aboutBannerRoot = dirname(__DIR__);
foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
    $candidate = 'assets/images/about/ezymart-about-banner.' . $ext;
    if (is_file($aboutBannerRoot . '/' . $candidate)) {
        $aboutBannerUrl = asset_image_url($candidate);
        break;
    }
}
?>

<style>
/* ===== Affinity Gems & Jewels About Page ===== */
.lumak-about-hero {
    position: relative;
    padding: clamp(56px, 10vw, 88px) 0 clamp(110px, 15vw, 140px);
    background: #002868;
    overflow: hidden;
    text-align: center;
    color: #fff;
    min-height: clamp(300px, 38vw, 480px);
    display: flex;
    align-items: center;
}
.lumak-about-hero.has-banner {
    background: #001f52;
}
.lumak-about-hero-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
}
.lumak-about-hero-bg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center center;
    display: block;
}
.lumak-about-hero-overlay {
    position: absolute;
    inset: 0;
    z-index: 1;
    background:
        linear-gradient(180deg, rgba(0, 40, 104, 0.55) 0%, rgba(0, 40, 104, 0.72) 52%, rgba(110, 80, 6, 0.88) 100%),
        linear-gradient(90deg, rgba(0, 40, 104, 0.35) 0%, transparent 35%, transparent 65%, rgba(0, 40, 104, 0.35) 100%);
    pointer-events: none;
}
.lumak-about-hero .container {
    position: relative;
    z-index: 2;
    width: 100%;
}
.lumak-about-hero::before,
.lumak-about-hero::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}
.lumak-about-hero::before {
    width: min(480px, 90vw);
    height: min(480px, 90vw);
    top: -180px;
    right: -120px;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
}
.lumak-about-hero::after {
    width: min(360px, 70vw);
    height: min(360px, 70vw);
    bottom: -140px;
    left: -80px;
    background: radial-gradient(circle, rgba(0, 40, 104, 0.22) 0%, transparent 70%);
}
.lumak-about-hero.has-banner::before,
.lumak-about-hero.has-banner::after {
    z-index: 2;
    opacity: 0.45;
}
.lumak-about-hero-inner {
    position: relative;
    z-index: 3;
    max-width: 760px;
    margin: 0 auto;
}
.lumak-about-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.22);
    backdrop-filter: blur(8px);
    padding: 8px 18px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 20px;
}
.lumak-about-hero h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 16px;
    letter-spacing: -0.02em;
}
.lumak-about-hero h1 span.gem-text-shimmer {
    background: linear-gradient(90deg, #ffffff 0%, #dbe5f5 25%, #fce96a 50%, #ffffff 75%, #dbe5f5 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: gemTextSheen 5s linear infinite;
}
@keyframes gemTextSheen {
    to { background-position: 200% center; }
}
.lumak-about-hero-lead {
    font-size: clamp(14px, 2.2vw, 16px);
    color: rgba(255,255,255,0.92);
    line-height: 1.75;
    max-width: 620px;
    margin: 0 auto;
}

/* Animated Sapphire Gemstone Centerpiece */
.hero-gem-emblem {
    position: relative;
    width: 68px;
    height: 68px;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.hero-gem-facet-ring {
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    border: 2px dashed rgba(252, 233, 106, 0.45);
    animation: rotateGemRing 16s linear infinite;
    pointer-events: none;
}
.hero-gem-center {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: radial-gradient(circle at 30% 30%, #3d7fe8 0%, #002868 70%, #001740 100%);
    border: 2px solid rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    color: #ffffff;
    box-shadow: 0 0 30px rgba(61, 127, 232, 0.6), 0 10px 24px rgba(0, 0, 0, 0.35);
    animation: gemPulseGlow 3.5s ease-in-out infinite;
    position: relative;
    z-index: 2;
}
.hero-gem-center i {
    animation: gemFacetGlitter 3s ease-in-out infinite;
    color: #ffffff;
}
.hero-gem-flare {
    position: absolute;
    width: 110px;
    height: 110px;
    background: radial-gradient(circle, rgba(252, 233, 106, 0.4) 0%, rgba(61, 127, 232, 0.25) 45%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    animation: flareBreath 3.5s ease-in-out infinite;
    z-index: 1;
}

@keyframes rotateGemRing {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
@keyframes gemPulseGlow {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 25px rgba(61, 127, 232, 0.5), 0 8px 20px rgba(0, 0, 0, 0.35);
    }
    50% {
        transform: scale(1.06);
        box-shadow: 0 0 45px rgba(100, 160, 255, 0.85), 0 0 20px rgba(252, 233, 106, 0.5);
    }
}
@keyframes gemFacetGlitter {
    0%, 100% { opacity: 0.95; filter: drop-shadow(0 0 4px rgba(255,255,255,0.6)); }
    50% { opacity: 1; filter: drop-shadow(0 0 14px rgba(255,255,255,1)) drop-shadow(0 0 8px rgba(252,233,106,0.85)); }
}
@keyframes flareBreath {
    0%, 100% { transform: scale(0.9); opacity: 0.6; }
    50% { transform: scale(1.2); opacity: 1; }
}

/* Floating Gemstone Facets & Sparkles */
.lumak-about-hero-hearts {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 2;
}
.lumak-about-hero-hearts i {
    position: absolute;
    color: rgba(255,255,255,0.2);
}
.lumak-about-hero-hearts .h1 { top: 18%; left: 8%; font-size: 26px; transform: rotate(-12deg); animation: gemFloatSlow 6s ease-in-out infinite; }
.lumak-about-hero-hearts .h2 { top: 26%; right: 10%; font-size: 32px; opacity: 0.7; animation: gemFloatSlow 7s ease-in-out infinite 1s; }
.lumak-about-hero-hearts .h3 { bottom: 32%; left: 12%; font-size: 22px; transform: rotate(8deg); animation: gemFloatSlow 8s ease-in-out infinite 2s; }

.gem-sparkle-star {
    position: absolute;
    color: #fce96a;
    font-size: 18px;
    pointer-events: none;
    animation: gemTwinkle 3.2s ease-in-out infinite;
}
.gem-sparkle-star.s1 { top: 16%; left: 18%; animation-delay: 0s; font-size: 22px; }
.gem-sparkle-star.s2 { top: 22%; right: 20%; animation-delay: 0.8s; font-size: 24px; color: #a5c7ff; }
.gem-sparkle-star.s3 { bottom: 28%; left: 22%; animation-delay: 1.6s; font-size: 16px; }
.gem-sparkle-star.s4 { bottom: 34%; right: 25%; animation-delay: 2.3s; font-size: 20px; color: #fce96a; }

@keyframes gemTwinkle {
    0%, 100% { transform: scale(0.4) rotate(0deg); opacity: 0.2; }
    50% { transform: scale(1.3) rotate(45deg); opacity: 0.95; filter: drop-shadow(0 0 10px #fce96a); }
}
@keyframes gemFloatSlow {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-14px) rotate(8deg); }
}

.lumak-about-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0;
    max-width: 920px;
    margin: clamp(-70px, -8vw, -50px) auto 0;
    position: relative;
    z-index: 2;
    background: #fff;
    border-radius: 18px;
    border: 1px solid #e8f0fe;
    box-shadow: 0 16px 48px rgba(0, 40, 104, 0.12);
    overflow: hidden;
}
.lumak-about-stat {
    text-align: center;
    padding: clamp(18px, 3vw, 26px) 12px;
    border-right: 1px solid #e8f0fe;
}
.lumak-about-stat:last-child { border-right: none; }
.lumak-about-stat strong {
    display: block;
    font-size: clamp(1.2rem, 2.5vw, 1.55rem);
    font-weight: 800;
    color: #002868;
    line-height: 1.2;
    margin-bottom: 4px;
}
.lumak-about-stat span {
    font-size: 11px;
    font-weight: 700;
    color: #002868;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.lumak-about-body {
    padding: clamp(48px, 7vw, 72px) 0 clamp(56px, 8vw, 80px);
    background: #FFFDF9;
}

.lumak-about-story {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr);
    gap: clamp(28px, 5vw, 48px);
    align-items: center;
    margin-bottom: clamp(40px, 6vw, 64px);
}
.lumak-about-story-visual {
    position: relative;
}
.lumak-about-story-frame {
    border-radius: 24px;
    overflow: hidden;
    border: 2px solid #e8f0fe;
    box-shadow: 0 20px 50px rgba(0, 40, 104, 0.14);
    line-height: 0;
    background: #fff;
    padding: 30px;
    text-align: center;
}
.lumak-about-story-frame img {
    width: 100%;
    max-width: 380px;
    height: auto;
    display: inline-block;
    object-fit: contain;
}
.lumak-about-story-float {
    position: absolute;
    bottom: -16px;
    right: -12px;
    background: #002868;
    color: #fff;
    padding: 14px 20px;
    border-radius: 14px;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 8px 24px rgba(0, 40, 104, 0.35);
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid rgba(255,255,255,0.2);
}
.lumak-about-story-float i { font-size: 16px; color: #fce96a; }
.lumak-about-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #002868;
    margin-bottom: 12px;
}
.lumak-about-story-text h2 {
    font-size: clamp(1.5rem, 3.5vw, 2.1rem);
    font-weight: 800;
    color: #002868;
    line-height: 1.25;
    margin-bottom: 16px;
}
.lumak-about-story-text p {
    font-size: 14px;
    color: #4b5563;
    line-height: 1.85;
    margin-bottom: 14px;
    text-align: justify;
    text-justify: inter-word;
}
.lumak-about-story-text p:last-child { margin-bottom: 0; }

/* China Expansion & Collaboration Section */
.affinity-expansion-section {
    background: #fff;
    border-radius: 24px;
    border: 1px solid #e8f0fe;
    box-shadow: 0 16px 48px rgba(0, 40, 104, 0.08);
    padding: clamp(32px, 5vw, 48px);
    margin-bottom: clamp(40px, 6vw, 64px);
}
.affinity-expansion-head {
    text-align: center;
    max-width: 720px;
    margin: 0 auto clamp(28px, 4vw, 36px);
}
.affinity-expansion-head h2 {
    font-size: clamp(1.4rem, 3vw, 1.9rem);
    font-weight: 800;
    color: #002868;
    line-height: 1.25;
    margin-bottom: 12px;
}
.affinity-expansion-head p {
    font-size: 14px;
    color: #555;
    line-height: 1.8;
}
.affinity-expansion-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 24px;
}
.affinity-expansion-card {
    background: #FFFDF9;
    border-radius: 18px;
    border: 1px solid #dbe5f5;
    padding: 28px 24px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
}
.affinity-expansion-card:hover {
    transform: translateY(-4px);
    border-color: #001f52;
    box-shadow: 0 12px 32px rgba(0, 40, 104, 0.12);
}
.affinity-card-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 999px;
    background: #e8f0fe;
    color: #001f52;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 14px;
    align-self: flex-start;
}
.affinity-expansion-card h3 {
    font-size: 17px;
    font-weight: 800;
    color: #002868;
    margin-bottom: 12px;
    line-height: 1.35;
}
.affinity-expansion-card p {
    font-size: 13.5px;
    color: #4b5563;
    line-height: 1.8;
    margin-bottom: 14px;
    text-align: justify;
}
.affinity-expansion-card p:last-child {
    margin-bottom: 0;
}

/* Director / leadership */
.ez-director-section {
    margin-bottom: clamp(40px, 6vw, 64px);
}
.ez-director-card {
    display: grid;
    grid-template-columns: minmax(280px, 36%) minmax(0, 1fr);
    background: #fff;
    border-radius: 20px;
    border: 1px solid #e8f0fe;
    box-shadow: 0 16px 48px rgba(0, 40, 104, 0.08);
    overflow: hidden;
    min-height: 400px;
}
.ez-director-visual {
    position: relative;
    min-height: 280px;
    overflow: hidden;
}
.ez-director-visual-panel {
    position: relative;
    width: 100%;
    height: 100%;
    min-height: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    padding: 32px 24px;
    background:
        radial-gradient(circle at 18% 22%, rgba(255, 255, 255, 0.14) 0%, transparent 42%),
        radial-gradient(circle at 82% 78%, rgba(0, 40, 104, 0.16) 0%, transparent 38%),
        #002868;
    color: #fff;
    text-align: center;
}
.ez-director-visual-glow {
    position: absolute;
    inset: auto;
    width: min(220px, 55%);
    height: min(220px, 55%);
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0, 40, 104, 0.25) 0%, transparent 72%);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
}
.ez-director-visual-icons {
    position: absolute;
    inset: 0;
    pointer-events: none;
}
.ez-director-visual-icons i {
    position: absolute;
    color: rgba(255, 255, 255, 0.15);
}
.ez-director-visual-icons .icon-1 {
    top: 14%;
    left: 12%;
    font-size: 28px;
    transform: rotate(-12deg);
}
.ez-director-visual-icons .icon-2 {
    top: 18%;
    right: 14%;
    font-size: 22px;
}
.ez-director-visual-icons .icon-3 {
    bottom: 16%;
    left: 16%;
    font-size: 20px;
    transform: rotate(8deg);
}
.ez-director-visual-center {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}
.ez-director-visual-logo {
    width: auto;
    height: 48px;
    object-fit: contain;
}
.ez-director-visual-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.ez-director-visual-badge i {
    color: #002868;
}
.ez-director-visual-name {
    font-size: clamp(1.1rem, 2vw, 1.35rem);
    font-weight: 800;
    line-height: 1.35;
    color: #fff;
    margin: 0;
}
.ez-director-visual-role {
    font-size: 12px;
    font-weight: 600;
    color: #e8f0fe;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.ez-director-message {
    padding: clamp(28px, 4vw, 38px) clamp(28px, 4vw, 40px);
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.ez-director-head {
    margin-bottom: 20px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e8f0fe;
}
.ez-director-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #002868;
    margin-bottom: 8px;
}
.ez-director-head h2 {
    font-size: clamp(1.3rem, 2.5vw, 1.65rem);
    font-weight: 800;
    color: #002868;
    line-height: 1.25;
    margin: 0 0 8px;
}
.ez-director-meta {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
    margin: 0;
}
.ez-director-meta strong {
    color: #002868;
    font-weight: 600;
}
.ez-director-message blockquote {
    margin: 0 0 22px;
    padding: 0;
    border: none;
}
.ez-director-message blockquote p {
    font-size: 14px;
    color: #475569;
    line-height: 1.85;
    margin: 0 0 12px;
    text-align: justify;
}
.ez-director-message blockquote p:last-child {
    margin-bottom: 0;
}
.ez-director-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    padding-top: 16px;
    border-top: 1px solid #e8f0fe;
    margin-top: auto;
}
.ez-director-footer-text {
    font-size: 12px;
    color: #64748b;
    line-height: 1.5;
}
.ez-director-footer-text strong {
    display: block;
    font-size: 13px;
    color: #002868;
    margin-bottom: 2px;
}
.ez-director-contact-links {
    display: flex;
    gap: 10px;
}
.ez-director-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
}
.ez-director-btn.btn-call {
    background: #002868;
    color: #fff;
}
.ez-director-btn.btn-wa {
    background: #25D366;
    color: #fff;
}

.lumak-about-pillars {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: clamp(40px, 6vw, 56px);
}
.lumak-about-pillar {
    background: #ffffff;
    border: 1px solid #dbe5f5;
    border-radius: 20px;
    padding: 34px 24px 30px;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0, 40, 104, 0.05);
    transition: all 0.35s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.lumak-about-pillar::before {
    content: '';
    position: absolute;
    top: 0;
    left: -120%;
    width: 80%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.65), transparent);
    transform: skewX(-25deg);
    transition: left 0.75s ease;
    pointer-events: none;
}
.lumak-about-pillar:hover::before {
    left: 150%;
}
.lumak-about-pillar:hover {
    transform: translateY(-6px);
    border-color: #003b94;
    box-shadow: 0 18px 40px rgba(0, 40, 104, 0.14);
}
.lumak-about-pillar-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 18px;
    border-radius: 18px;
    background: linear-gradient(135deg, #002868 0%, #0042a5 100%);
    color: #ffffff !important;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    border: 2px solid rgba(255, 255, 255, 0.6);
    box-shadow: 0 10px 24px rgba(0, 40, 104, 0.28), inset 0 2px 4px rgba(255, 255, 255, 0.3);
    transition: all 0.35s ease;
}
.lumak-about-pillar-icon i {
    color: #ffffff !important;
    filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.3));
    transition: transform 0.3s ease, color 0.3s ease;
}
.lumak-about-pillar:hover .lumak-about-pillar-icon {
    background: linear-gradient(135deg, #003b94 0%, #001f52 100%);
    transform: scale(1.1) rotate(4deg);
    box-shadow: 0 14px 30px rgba(0, 40, 104, 0.38), 0 0 16px rgba(0, 59, 148, 0.45);
}
.lumak-about-pillar:hover .lumak-about-pillar-icon i {
    color: #fce96a !important;
    transform: scale(1.15);
}
.lumak-about-pillar h3 {
    font-size: 17px;
    font-weight: 800;
    color: #002868;
    margin-bottom: 12px;
    letter-spacing: -0.01em;
}
.lumak-about-pillar p {
    font-size: 13px;
    color: #666;
    line-height: 1.7;
    margin: 0;
}

.lumak-about-section-head {
    text-align: center;
    max-width: 640px;
    margin: 0 auto clamp(28px, 4vw, 40px);
}
.lumak-about-section-head h2 {
    font-size: clamp(1.4rem, 3vw, 1.85rem);
    font-weight: 800;
    color: #002868;
    margin-bottom: 10px;
}
.lumak-about-section-head p {
    font-size: 14px;
    color: #666;
    line-height: 1.7;
    margin: 0;
}

.lumak-about-values {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: clamp(40px, 6vw, 56px);
}
.lumak-about-value {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    background: #fff;
    border: 1px solid #e8f0fe;
    border-radius: 16px;
    padding: 22px 20px;
    transition: border-color 0.2s;
}
.lumak-about-value:hover { border-color: #001f52; }
.lumak-about-value > i {
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    border-radius: 12px;
    background: #f4f7fc;
    color: #002868;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.lumak-about-value h4 {
    font-size: 15px;
    font-weight: 700;
    color: #002868;
    margin-bottom: 6px;
}
.lumak-about-value p {
    font-size: 13px;
    color: #666;
    line-height: 1.65;
    margin: 0;
}

.lumak-about-contact-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: clamp(32px, 5vw, 48px);
}
.lumak-about-contact-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e8f0fe;
    border-radius: 14px;
}
.lumak-about-contact-item > i {
    font-size: 22px;
    color: #002868;
    flex-shrink: 0;
}
.lumak-about-contact-item strong {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #002868;
    margin-bottom: 2px;
}
.lumak-about-contact-item span,
.lumak-about-contact-item a {
    font-size: 12px;
    color: #666;
    text-decoration: none;
    line-height: 1.5;
}
.lumak-about-contact-item a:hover { color: #002868; }

.lumak-about-cta {
    background: linear-gradient(135deg, #002868 0%, #0d3882 100%);
    border-radius: 22px;
    padding: clamp(36px, 5vw, 52px) clamp(24px, 4vw, 40px);
    text-align: center;
    color: #fff;
    position: relative;
    overflow: hidden;
    border: 1px solid #e8f0fe;
}
.lumak-about-cta::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 80% 20%, rgba(0, 40, 104, 0.2) 0%, transparent 50%);
    pointer-events: none;
}
.lumak-about-cta h3 {
    font-size: clamp(1.3rem, 3vw, 1.75rem);
    font-weight: 800;
    margin-bottom: 10px;
    position: relative;
    color: #f4f7fc;
}
.lumak-about-cta > p {
    font-size: 14px;
    color: rgba(255,255,255,0.9);
    margin-bottom: 24px;
    max-width: 580px;
    margin-left: auto;
    margin-right: auto;
    position: relative;
}
.lumak-about-cta-btns {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    position: relative;
}
.lumak-about-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 26px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.lumak-about-cta-btn.primary {
    background: linear-gradient(135deg, #f59e0b 0%, #eab308 50%, #d97706 100%);
    color: #001f52 !important;
    font-size: 14px;
    font-weight: 700;
    padding: 14px 30px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.55);
    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.42), 0 2px 6px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
}
.lumak-about-cta-btn.primary i {
    color: #001f52 !important;
    font-size: 15px;
    transition: transform 0.3s ease;
}
.lumak-about-cta-btn.primary::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -120%;
    width: 60%;
    height: 200%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.75), transparent);
    transform: rotate(25deg);
    animation: gemButtonGleam 4s ease-in-out infinite;
}
@keyframes gemButtonGleam {
    0% { left: -120%; }
    25%, 100% { left: 160%; }
}
.lumak-about-cta-btn.primary:hover {
    background: linear-gradient(135deg, #d97706 0%, #ca8a04 100%);
    color: #ffffff !important;
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(217, 119, 6, 0.55);
}
.lumak-about-cta-btn.primary:hover i {
    color: #ffffff !important;
    transform: scale(1.2) rotate(10deg);
}
.lumak-about-cta-btn.secondary {
    background: rgba(255,255,255,0.12);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.28);
}
.lumak-about-cta-btn.secondary:hover {
    background: rgba(255,255,255,0.22);
    color: #fff;
}
.lumak-about-cta-btn.wa-btn {
    background: #25D366;
    color: #fff;
}
.lumak-about-cta-btn.wa-btn:hover {
    background: #20ba5a;
    color: #fff;
    transform: translateY(-2px);
}

@media (min-width: 993px) {
    .ez-director-visual,
    .ez-director-visual-panel {
        min-height: 100%;
        height: 100%;
    }
}
@media (max-width: 992px) {
    .lumak-about-story { grid-template-columns: 1fr; }
    .lumak-about-story-float { right: 12px; bottom: -12px; }
    .affinity-expansion-grid { grid-template-columns: 1fr; }
    .ez-director-card { grid-template-columns: 1fr; min-height: 0; }
    .ez-director-visual { max-height: 360px; overflow: hidden; }
    .ez-director-visual-panel { min-height: 360px; }
    .lumak-about-pillars { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .lumak-about-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .lumak-about-stat:nth-child(2) { border-right: none; }
    .lumak-about-stat:nth-child(-n+2) { border-bottom: 1px solid #e8f0fe; }
    .lumak-about-values { grid-template-columns: 1fr; }
    .lumak-about-contact-strip { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
    .lumak-about-cta-btns { flex-direction: column; align-items: stretch; }
    .lumak-about-cta-btn { justify-content: center; }
}
</style>

<!-- Hero -->
<header class="lumak-about-hero<?php echo $aboutBannerUrl ? ' has-banner' : ''; ?>">
    <?php if ($aboutBannerUrl): ?>
    <div class="lumak-about-hero-bg" aria-hidden="true">
        <img src="<?php echo htmlspecialchars($aboutBannerUrl); ?>"
             alt=""
             width="1920"
             height="520"
             loading="eager"
             fetchpriority="high"
             decoding="async">
    </div>
    <div class="lumak-about-hero-overlay" aria-hidden="true"></div>
    <?php endif; ?>
    <div class="lumak-about-hero-hearts" aria-hidden="true">
        <i class="fas fa-gem h1"></i>
        <i class="fas fa-certificate h2"></i>
        <i class="fas fa-ring h3"></i>
        <span class="gem-sparkle-star s1">✦</span>
        <span class="gem-sparkle-star s2">✦</span>
        <span class="gem-sparkle-star s3">✧</span>
        <span class="gem-sparkle-star s4">✦</span>
    </div>
    <div class="container">
        <div class="lumak-about-hero-inner">
            <div class="hero-gem-emblem" aria-hidden="true">
                <div class="hero-gem-facet-ring"></div>
                <div class="hero-gem-flare"></div>
                <div class="hero-gem-center"><i class="fas fa-gem"></i></div>
            </div>
            <span class="lumak-about-hero-badge"><i class="fas fa-gem"></i> Direct Source for Sri Lankan Sapphires</span>
            <h1>Introduction to <span class="gem-text-shimmer">Affinity Gems &amp; Jewels</span></h1>
            <p class="lumak-about-hero-lead">Serving as a direct source supplier of high-quality Sri Lankan sapphires, ethically sourced and meticulously graded. Registered with the State Gem Corporation of Sri Lanka and member of the International Chamber of Commerce.</p>
        </div>
    </div>
</header>

<!-- Stats -->
<div class="container">
    <div class="lumak-about-stats">
        <div class="lumak-about-stat"><strong>100%</strong><span>Authentic Sapphires</span></div>
        <div class="lumak-about-stat"><strong>State Gem Corp</strong><span>Registered Supplier</span></div>
        <div class="lumak-about-stat"><strong>ICC Member</strong><span>Chamber of Commerce</span></div>
        <div class="lumak-about-stat"><strong>Worldwide</strong><span>Insured Delivery</span></div>
    </div>
</div>

<section class="lumak-about-body">
    <div class="container">

        <!-- Our Story -->
        <div class="lumak-about-story">
            <div class="lumak-about-story-visual">
                <div class="lumak-about-story-frame">
                    <img src="<?php echo asset_image_url('assets/images/logo.png'); ?>" alt="Affinity Gems & Jewels Logo" loading="lazy">
                </div>
                <div class="lumak-about-story-float">
                    <i class="fas fa-award"></i> Direct Source Supplier
                </div>
            </div>
            <div class="lumak-about-story-text">
                <span class="lumak-about-eyebrow"><i class="fas fa-gem"></i> Heritage &amp; Excellence</span>
                <h2>Your Direct Source for Sri Lankan Sapphires</h2>
                <p>In the world of gemstones, Sri Lanka is renowned for its exceptional sapphires, which are celebrated not only for their breathtaking beauty but also for their deep cultural roots and economic significance. <strong>Affinity Gems and Jewels Pvt Ltd</strong> stands at the forefront of this illustrious tradition, serving as a direct source supplier of high-quality Sri Lankan sapphires.</p>
                <p>With a legacy deeply embedded in the gem industry, we are a registered company with the <strong>State Gem Corporation of Sri Lanka</strong> and a long-standing member of the <strong>International Chamber of Commerce in Sri Lanka</strong>. Our reputation as a trusted entity in both local and international markets has been built on generations of expertise and a commitment to excellence.</p>
                <p>At Affinity Gems, we prioritize quality, authenticity, and outstanding customer service. Our sapphires are ethically sourced and meticulously graded, ensuring they meet the highest industry standards. We recognize that in the luxury market, reputation is paramount, and we are dedicated to maintaining the integrity of our brand.</p>
            </div>
        </div>

        <!-- China Expansion & Collaboration Section -->
        <div class="affinity-expansion-section">
            <div class="affinity-expansion-head">
                <span class="lumak-about-eyebrow"><i class="fas fa-globe-asia"></i> Global Market Presence</span>
                <h2>The Importance of Expanding Our Business Network in China</h2>
                <p>The Chinese market is experiencing a growing demand for high-quality gemstones, particularly sapphires. This presents a timely opportunity for Affinity Gems Pvt Ltd to expand our sales and marketing efforts in the region. By establishing a presence in China, we can tap into a lucrative market and position ourselves as a leading supplier of Sri Lankan sapphires. Our goal is to create personalized experiences for our clients, allowing them to appreciate the inherent beauty and value of our gemstones firsthand.</p>
            </div>

            <div class="affinity-expansion-grid">
                <div class="affinity-expansion-card">
                    <div>
                        <span class="affinity-card-badge"><i class="fas fa-handshake"></i> Partnership</span>
                        <h3>A Proposal for Collaboration</h3>
                        <p>To facilitate this expansion, we humbly request your assistance in connecting us with your network of friends and business associates in China. Your connections could be instrumental in arranging meetings with potential clients who are seeking high-quality gemstones.</p>
                        <p>We are more than willing to organize personal visits to showcase our stock at the convenience of interested parties. This collaborative approach not only enhances our market presence but also fosters a spirit of partnership that can lead to mutually beneficial outcomes.</p>
                    </div>
                </div>

                <div class="affinity-expansion-card">
                    <div>
                        <span class="affinity-card-badge"><i class="fas fa-medal"></i> Our Commitment</span>
                        <h3>Conclusion &amp; Global Outlook</h3>
                        <p>The opportunity to extend Affinity Gems Pvt Ltd's reach in the Chinese market is both timely and essential for our growth. Our unwavering commitment to quality, ethical sourcing, and exceptional service distinguishes us from our competitors. We are excited about the prospect of collaborating with new partners in China and look forward to showcasing the beauty and value of Sri Lankan sapphires.</p>
                        <p>We sincerely hope that you will consider our request for introductions and join us in this venture. Together, we can promote the exquisite allure of Sri Lankan sapphires and forge lasting partnerships that benefit all involved. Thank you for your time and consideration.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Director / Leadership Section -->
        <div class="ez-director-section">
            <div class="ez-director-card">
                <div class="ez-director-visual">
                    <div class="ez-director-visual-panel" aria-hidden="true">
                        <div class="ez-director-visual-glow"></div>
                        <div class="ez-director-visual-icons">
                            <i class="fas fa-gem icon-1"></i>
                            <i class="fas fa-certificate icon-2"></i>
                            <i class="fas fa-crown icon-3"></i>
                        </div>
                        <div class="ez-director-visual-center">
                            <img src="<?php echo asset_image_url('assets/images/logobottom.png'); ?>" alt="<?php echo htmlspecialchars(SITE_NAME); ?>" class="ez-director-visual-logo" loading="lazy">
                            <span class="ez-director-visual-badge"><i class="fas fa-user-tie"></i> Executive Leadership</span>
                            <p class="ez-director-visual-name"><?php echo htmlspecialchars(STORE_DIRECTOR); ?></p>
                            <span class="ez-director-visual-role"><?php echo htmlspecialchars(STORE_DIRECTOR_TITLE); ?> · <?php echo htmlspecialchars(STORE_OWNER); ?></span>
                        </div>
                    </div>
                </div>
                <div class="ez-director-message">
                    <div class="ez-director-head">
                        <span class="ez-director-label">Managing Director's Statement</span>
                        <h2><?php echo htmlspecialchars(STORE_DIRECTOR); ?></h2>
                        <p class="ez-director-meta"><strong><?php echo htmlspecialchars(STORE_DIRECTOR_TITLE); ?></strong> · <?php echo htmlspecialchars(STORE_OWNER); ?></p>
                    </div>
                    <blockquote>
                        <p>Welcome to <strong>Affinity Gems and Jewels Pvt Ltd</strong>. Our journey in the gemstone industry is rooted in a lifelong dedication to the rare, natural gemstones of Sri Lanka. We believe that every sapphire tells a unique geological story of timeless beauty and cultural depth.</p>
                        <p>As a registered direct source supplier with the State Gem Corporation of Sri Lanka and member of the International Chamber of Commerce, we take personal pride in every stone we inspect, cut, and certify. Whether you are a private collector, jewelry connoisseur, or international trade partner in China and beyond, our promise to you is complete authenticity, ethical provenance, and unmatched personal care.</p>
                        <p>We look forward to forging lasting relationships with you.</p>
                    </blockquote>
                    <div class="ez-director-footer">
                        <div class="ez-director-footer-text">
                            <strong><?php echo htmlspecialchars(STORE_DIRECTOR); ?></strong>
                            <?php echo htmlspecialchars(STORE_DIRECTOR_TITLE); ?>, <?php echo htmlspecialchars(STORE_OWNER); ?>
                        </div>
                        <div class="ez-director-contact-links">
                            <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_1); ?>" class="ez-director-btn btn-call"><i class="fas fa-phone-alt"></i> Call</a>
                            <a href="<?php echo htmlspecialchars(STORE_WHATSAPP_URL); ?>?text=<?php echo rawurlencode('Hello Murshid Munzeer, I am interested in collaborating with Affinity Gems & Jewels.'); ?>" target="_blank" rel="noopener noreferrer" class="ez-director-btn btn-wa"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mission pillars -->
        <div class="lumak-about-pillars">
            <div class="lumak-about-pillar">
                <div class="lumak-about-pillar-icon"><i class="fas fa-bullseye"></i></div>
                <h3>Our Mission</h3>
                <p>To serve as the most trusted direct-source bridge between Sri Lanka's finest sapphire mines and discerning clients and partners globally.</p>
            </div>
            <div class="lumak-about-pillar">
                <div class="lumak-about-pillar-icon"><i class="fas fa-gem"></i></div>
                <h3>Our Vision</h3>
                <p>To showcase the world-renowned beauty and cultural heritage of Ceylon sapphires on the global luxury stage through uncompromised excellence.</p>
            </div>
            <div class="lumak-about-pillar">
                <div class="lumak-about-pillar-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Our Promise</h3>
                <p>Every gemstone is ethically unearthed, meticulously graded, laboratory certified, and backed by our personal guarantee of authenticity.</p>
            </div>
        </div>

        <!-- Values -->
        <div class="lumak-about-section-head">
            <h2>What We Stand For</h2>
            <p>The foundational principles guiding every gemstone collection and partnership at Affinity Gems.</p>
        </div>
        <div class="lumak-about-values">
            <div class="lumak-about-value">
                <i class="fas fa-certificate"></i>
                <div>
                    <h4>State Gem Corp Registered</h4>
                    <p>Officially licensed and registered with the State Gem Corporation of Sri Lanka and member of the International Chamber of Commerce.</p>
                </div>
            </div>
            <div class="lumak-about-value">
                <i class="fas fa-leaf"></i>
                <div>
                    <h4>Ethical &amp; Conflict-Free</h4>
                    <p>Mined with environmental mindfulness from traditional Ceylon gem fields and cut by master gem artisans.</p>
                </div>
            </div>
            <div class="lumak-about-value">
                <i class="fas fa-microscope"></i>
                <div>
                    <h4>Meticulous Grading</h4>
                    <p>Each sapphire is evaluated for color saturation, clarity, cut precision, and brilliance with international laboratory certification.</p>
                </div>
            </div>
            <div class="lumak-about-value">
                <i class="fas fa-globe"></i>
                <div>
                    <h4>Personal Showcase Visits</h4>
                    <p>We organize personal showcase visits for partners and clients in China and internationally to experience our collection firsthand.</p>
                </div>
            </div>
        </div>

        <!-- Contact Strip -->
        <div class="lumak-about-contact-strip">
            <div class="lumak-about-contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <strong>Office &amp; Gallery</strong>
                    <span><?php echo STORE_LOCATION; ?></span>
                </div>
            </div>
            <div class="lumak-about-contact-item">
                <i class="fas fa-phone-alt"></i>
                <div>
                    <strong>Direct Hotline</strong>
                    <a href="tel:<?php echo preg_replace('/\s+/', '', STORE_PHONE_1); ?>"><?php echo STORE_PHONE_1; ?></a>
                </div>
            </div>
            <div class="lumak-about-contact-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <strong>Direct Email</strong>
                    <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="lumak-about-cta">
            <h3>Connect with Affinity Gems &amp; Jewels</h3>
            <p>Interested in exploring our sapphire collection, discussing trade partnerships, or scheduling a personal showcase viewing? We warmly welcome your inquiry.</p>
            <div class="lumak-about-cta-btns">
                <a href="<?php echo BASE_URL; ?>shop.php" class="lumak-about-cta-btn primary"><i class="fas fa-gem"></i> View Sapphire Collection</a>
                <a href="<?php echo BASE_URL; ?>contact.php" class="lumak-about-cta-btn secondary"><i class="fas fa-envelope"></i> Contact Us</a>
                <a href="<?php echo htmlspecialchars(STORE_WHATSAPP_URL); ?>?text=<?php echo rawurlencode('Hello Murshid Munzeer, I would like to inquire about Sri Lankan sapphires.'); ?>" target="_blank" rel="noopener noreferrer" class="lumak-about-cta-btn wa-btn"><i class="fab fa-whatsapp"></i> WhatsApp Us</a>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
