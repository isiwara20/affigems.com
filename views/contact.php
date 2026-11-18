<?php
$pageTitle = "Contact Us - ARIGATO.LK";
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/sms.php';
require_once __DIR__ . '/partials/header.php';

$contactBannerUrl = null;
$contactBannerRoot = dirname(__DIR__);
foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
    $candidate = 'assets/images/contact/ezymart-contact-banner.' . $ext;
    if (is_file($contactBannerRoot . '/' . $candidate)) {
        $contactBannerUrl = asset_image_url($candidate);
        break;
    }
}
?>

<style>
/* ===== ARIGATO.LK Contact Page (matches About design) ===== */
.lumak-contact-hero {
    position: relative;
    padding: clamp(56px, 10vw, 88px) 0 clamp(110px, 15vw, 140px);
    background: #D49A0A;
    overflow: hidden;
    text-align: center;
    color: #fff;
    min-height: clamp(300px, 38vw, 480px);
    display: flex;
    align-items: center;
}
.lumak-contact-hero.has-banner {
    background: #8A6508;
}
.lumak-contact-hero-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
}
.lumak-contact-hero-bg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center center;
    display: block;
}
.lumak-contact-hero-overlay {
    position: absolute;
    inset: 0;
    z-index: 1;
    background:
        linear-gradient(180deg, rgba(138, 101, 8, 0.55) 0%, rgba(196, 138, 10, 0.72) 52%, rgba(110, 80, 6, 0.88) 100%),
        linear-gradient(90deg, rgba(138, 101, 8, 0.35) 0%, transparent 35%, transparent 65%, rgba(138, 101, 8, 0.35) 100%);
    pointer-events: none;
}
.lumak-contact-hero .container {
    position: relative;
    z-index: 2;
    width: 100%;
}
.lumak-contact-hero::before,
.lumak-contact-hero::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}
.lumak-contact-hero::before {
    width: min(480px, 90vw);
    height: min(480px, 90vw);
    top: -180px;
    right: -120px;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
}
.lumak-contact-hero::after {
    width: min(360px, 70vw);
    height: min(360px, 70vw);
    bottom: -140px;
    left: -80px;
    background: radial-gradient(circle, rgba(224, 168, 16, 0.22) 0%, transparent 70%);
}
.lumak-contact-hero.has-banner::before,
.lumak-contact-hero.has-banner::after {
    z-index: 2;
    opacity: 0.45;
}
.lumak-contact-hero-inner {
    position: relative;
    z-index: 3;
    max-width: 680px;
    margin: 0 auto;
}
.lumak-contact-hero-badge {
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
.lumak-contact-hero h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 16px;
    letter-spacing: -0.02em;
}
.lumak-contact-hero h1 span {
    background: #FFF3D6;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.lumak-contact-hero-lead {
    font-size: clamp(14px, 2.2vw, 16px);
    color: rgba(255,255,255,0.88);
    line-height: 1.75;
    max-width: 520px;
    margin: 0 auto;
}
.lumak-contact-hero-hearts {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 2;
}
.lumak-contact-hero-hearts i {
    position: absolute;
    color: rgba(255,255,255,0.12);
}
.lumak-contact-hero-hearts .h1 { top: 18%; left: 8%; font-size: 20px; transform: rotate(-12deg); }
.lumak-contact-hero-hearts .h2 { top: 28%; right: 10%; font-size: 28px; opacity: 0.7; }
.lumak-contact-hero-hearts .h3 { bottom: 32%; left: 12%; font-size: 16px; transform: rotate(8deg); }

.lumak-contact-quick {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0;
    max-width: 960px;
    margin: clamp(-70px, -8vw, -50px) auto 0;
    position: relative;
    z-index: 2;
    background: #fff;
    border-radius: 18px;
    border: 1px solid #ebe0ff;
    box-shadow: 0 16px 48px rgba(229, 169, 26, 0.12);
    overflow: hidden;
}
.lumak-contact-quick-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 8px;
    padding: clamp(18px, 3vw, 24px) 12px;
    border-right: 1px solid #FFE8A8;
    text-decoration: none;
    color: inherit;
    transition: background 0.2s;
}
a.lumak-contact-quick-item {
    cursor: pointer;
    -webkit-tap-highlight-color: rgba(229, 169, 26, 0.12);
}
.lumak-contact-quick-item--static {
    cursor: default;
}
.lumak-contact-quick-item:last-child { border-right: none; }
.lumak-contact-quick-item:hover { background: #faf8ff; color: inherit; }
.lumak-contact-quick-item > i {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #D49A0A;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    box-shadow: 0 6px 18px rgba(229, 169, 26, 0.25);
}
.lumak-contact-quick-item strong {
    font-size: 12px;
    font-weight: 700;
    color: #1a1a2e;
}
.lumak-contact-quick-item span {
    font-size: 11px;
    color: #666;
    line-height: 1.4;
}
.lumak-contact-quick-phones {
    display: block;
    width: 100%;
}
.lumak-contact-quick-phones .store-phone-table {
    margin-inline: auto;
}

.lumak-contact-body {
    padding: clamp(48px, 7vw, 72px) 0 clamp(56px, 8vw, 80px);
    background: #FFF8E6;
}

.lumak-contact-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.25fr);
    gap: clamp(20px, 3vw, 28px);
    align-items: start;
    margin-bottom: clamp(32px, 5vw, 48px);
}

.lumak-contact-info-stack {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.lumak-contact-info-card {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    background: #fff;
    border: 1px solid #ebe0ff;
    border-radius: 16px;
    padding: 22px 20px;
    transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
}
.lumak-contact-info-card:hover {
    transform: translateY(-2px);
    border-color: #d4c0ff;
    box-shadow: 0 12px 32px rgba(229, 169, 26, 0.08);
}
.lumak-contact-info-icon {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    border-radius: 14px;
    background: #D49A0A;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: 0 6px 18px rgba(229, 169, 26, 0.25);
}
.lumak-contact-info-icon.whatsapp { background: #25d366; box-shadow: 0 6px 18px rgba(37, 211, 102, 0.25); }
.lumak-contact-info-content h3 {
    font-size: 14px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 6px;
}
.lumak-contact-info-content p {
    font-size: 13px;
    color: #666;
    line-height: 1.65;
    margin: 0;
}
.lumak-contact-info-content a {
    color: #E5A91A;
    font-weight: 600;
    text-decoration: none;
}
.lumak-contact-info-content a:hover { text-decoration: underline; }

.lumak-contact-phones {
    margin-top: 2px;
}

.lumak-contact-social {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    flex-wrap: wrap;
}
.lumak-contact-social a {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #C48A0A;
    border: 1px solid #C48A0A;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s;
}
.lumak-contact-social a:hover {
    background: #8A6508;
    border-color: #8A6508;
    color: #fff;
    transform: translateY(-2px);
}
.lumak-contact-social a[aria-label="Facebook"] {
    background: #1877f2;
    border-color: #1877f2;
}
.lumak-contact-social a[aria-label="Facebook"]:hover {
    background: #0f5dcb;
    border-color: #0f5dcb;
}
.lumak-contact-social a[aria-label="Instagram"] {
    background: #e4405f;
    border-color: #e4405f;
}
.lumak-contact-social a[aria-label="Instagram"]:hover {
    background: #c13584;
    border-color: #c13584;
}
.lumak-contact-social a[aria-label="TikTok"] {
    background: #010101;
    border-color: #010101;
}
.lumak-contact-social a[aria-label="TikTok"]:hover {
    background: #333;
    border-color: #333;
}
.lumak-contact-social a[aria-label="WhatsApp"] {
    background: #25d366;
    border-color: #25d366;
}
.lumak-contact-social a[aria-label="WhatsApp"]:hover {
    background: #1da851;
    border-color: #1da851;
}

.lumak-contact-form-card {
    background: #fff;
    border: 1px solid #ebe0ff;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(229, 169, 26, 0.1);
    font-family: 'Poppins', system-ui, -apple-system, sans-serif;
}
.lumak-contact-form-header {
    padding: clamp(24px, 4vw, 32px) clamp(24px, 4vw, 36px);
    background: #D49A0A;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.lumak-contact-form-header::before {
    content: '';
    position: absolute;
    top: -40px;
    right: -40px;
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
    pointer-events: none;
}
.lumak-contact-form-header h2 {
    font-size: clamp(1.1rem, 2.5vw, 1.35rem);
    font-weight: 800;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
}
.lumak-contact-form-header p {
    font-size: 14px;
    font-weight: 500;
    color: rgba(255,255,255,0.9);
    margin: 0;
    position: relative;
}
.lumak-contact-form-body {
    padding: clamp(24px, 4vw, 32px) clamp(24px, 4vw, 36px);
}

.lumak-contact-form {
    font-family: 'Poppins', system-ui, -apple-system, sans-serif;
}

.lumak-contact-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}
.lumak-contact-form .form-group {
    margin-bottom: 14px;
    position: relative;
}
.lumak-contact-form .form-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #0d1137;
    margin-bottom: 8px;
    text-transform: none;
    letter-spacing: 0;
    line-height: 1.3;
}
.lumak-contact-form .form-group input,
.lumak-contact-form .form-group textarea,
.lumak-contact-form .form-group select {
    width: 100%;
    padding: 14px 16px 14px 42px;
    border: 1.5px solid #e4e8f0;
    border-radius: 12px;
    font-family: 'Poppins', system-ui, -apple-system, sans-serif;
    font-size: 14px;
    font-weight: 600;
    outline: none;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    color: #1a1a2e;
    -webkit-font-smoothing: antialiased;
}
.lumak-contact-form .form-group input::placeholder,
.lumak-contact-form .form-group textarea::placeholder {
    font-family: 'Poppins', system-ui, -apple-system, sans-serif;
    font-weight: 500;
    color: #9aa3b8;
}
.lumak-contact-form .form-group textarea,
.lumak-contact-form .form-group select {
    padding-left: 16px;
}
.lumak-contact-form .form-group select {
    font-weight: 600;
    color: #0d1137;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%230052D4' d='M1.41 0 6 4.58 10.59 0 12 1.41l-6 6-6-6z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px;
}
.lumak-contact-form .form-group select option {
    font-family: 'Poppins', system-ui, sans-serif;
    font-weight: 600;
    color: #1a1a2e;
}
.lumak-contact-form .form-group input:focus,
.lumak-contact-form .form-group textarea:focus,
.lumak-contact-form .form-group select:focus {
    border-color: #E5A91A;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(229, 169, 26, 0.08);
}
.lumak-contact-form .form-group textarea {
    min-height: 120px;
    resize: vertical;
}
.lumak-contact-form .form-group .input-icon {
    position: absolute;
    left: 14px;
    top: 39px;
    color: #E5A91A;
    font-size: 14px;
    pointer-events: none;
    opacity: 0.85;
}
.lumak-contact-form .form-group:focus-within .input-icon { color: #E5A91A; }

.lumak-contact-form-actions {
    margin-top: 8px;
}
.lumak-contact-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 22px;
    border: none;
    border-radius: 12px;
    font-family: 'Poppins', system-ui, -apple-system, sans-serif;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.01em;
    cursor: pointer;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    -webkit-font-smoothing: antialiased;
}
.lumak-contact-btn.submit {
    width: 100%;
    flex: none;
    min-width: 0;
    background: #D49A0A;
    color: #fff;
    box-shadow: 0 8px 22px rgba(229, 169, 26, 0.28);
    cursor: pointer;
    opacity: 1;
}
.lumak-contact-btn.submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(229, 169, 26, 0.35);
    color: #fff;
}
.lumak-contact-btn.submit.is-loading {
    opacity: 0.72;
    cursor: wait;
    pointer-events: none;
    transform: none;
}

.lumak-contact-alert-slot {
    min-height: 52px;
    margin-top: 12px;
}
.lumak-contact-alert {
    margin: 0;
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
    border: 1px solid transparent;
    background: transparent;
    color: transparent;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease, background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}
.lumak-contact-alert.is-error.is-visible {
    opacity: 1;
    visibility: visible;
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}

.lumak-contact-success-overlay {
    position: fixed;
    inset: 0;
    z-index: 12000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 35, 90, 0.55);
    backdrop-filter: blur(4px);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.28s ease, visibility 0.28s ease;
}
.lumak-contact-success-overlay.is-visible {
    opacity: 1;
    visibility: visible;
}
.lumak-contact-success-modal {
    width: 100%;
    max-width: 440px;
    background: #fff;
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 24px 64px rgba(196, 138, 10, 0.22);
    transform: translateY(16px) scale(0.98);
    transition: transform 0.28s ease;
}
.lumak-contact-success-overlay.is-visible .lumak-contact-success-modal {
    transform: translateY(0) scale(1);
}
.lumak-contact-success-head {
    padding: 28px 28px 20px;
    text-align: center;
    background: #D49A0A;
    color: #fff;
}
.lumak-contact-success-icon {
    width: 58px;
    height: 58px;
    margin: 0 auto 14px;
    border-radius: 50%;
    background: rgba(255,255,255,0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
}
.lumak-contact-success-head h3 {
    margin: 0 0 6px;
    font-size: 1.25rem;
    font-weight: 800;
}
.lumak-contact-success-head p {
    margin: 0;
    font-size: 13px;
    color: rgba(255,255,255,0.9);
    line-height: 1.6;
}
.lumak-contact-success-body {
    padding: 24px 28px 28px;
    text-align: center;
}
.lumak-contact-ref-label {
    margin: 0 0 8px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #888;
}
.lumak-contact-ref-no {
    display: inline-block;
    padding: 12px 20px;
    margin-bottom: 18px;
    border-radius: 12px;
    background: #FFF8E6;
    border: 2px dashed #b8d4ff;
    font-size: 1.35rem;
    font-weight: 800;
    color: #D4A017;
    letter-spacing: 0.06em;
}
.lumak-contact-success-note {
    margin: 0 0 8px;
    font-size: 14px;
    line-height: 1.7;
    color: #555;
}
.lumak-contact-success-sla {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 10px 0 20px;
    padding: 10px 14px;
    border-radius: 999px;
    background: #ecfdf3;
    border: 1px solid #bbf7d0;
    color: #16a34a;
    font-size: 12px;
    font-weight: 700;
}
.lumak-contact-success-close {
    width: 100%;
    padding: 14px 22px;
    border: none;
    border-radius: 12px;
    background: #D49A0A;
    color: #fff;
    font-family: 'Poppins', system-ui, sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}
.lumak-contact-success-close:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(229, 169, 26, 0.25);
}

.lumak-contact-section-head {
    text-align: center;
    max-width: 560px;
    margin: 0 auto clamp(24px, 4vw, 32px);
}
.lumak-contact-section-head h2 {
    font-size: clamp(1.3rem, 3vw, 1.6rem);
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 8px;
}
.lumak-contact-section-head p {
    font-size: 14px;
    color: #666;
    line-height: 1.7;
    margin: 0;
}

.lumak-contact-help-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: clamp(32px, 5vw, 48px);
}
.lumak-contact-help-card {
    background: #fff;
    border: 1px solid #ebe0ff;
    border-radius: 16px;
    padding: 22px 20px;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition: all 0.25s;
}
.lumak-contact-help-card:hover {
    border-color: #E5A91A;
    background: #faf8ff;
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(229, 169, 26, 0.08);
    color: inherit;
}
.lumak-contact-help-card i {
    font-size: 24px;
    color: #E5A91A;
    margin-bottom: 12px;
    display: block;
}
.lumak-contact-help-card strong {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 6px;
}
.lumak-contact-help-card span {
    font-size: 12px;
    color: #666;
    line-height: 1.5;
}

.lumak-contact-map-wrap {
    border-radius: 22px;
    overflow: hidden;
    border: 1px solid #ebe0ff;
    box-shadow: 0 16px 48px rgba(229, 169, 26, 0.08);
    background: #fff;
    margin-bottom: clamp(32px, 5vw, 48px);
}
.lumak-contact-map-wrap iframe {
    width: 100%;
    height: clamp(260px, 40vw, 340px);
    border: none;
    display: block;
}
.lumak-contact-map-footer {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 24px;
    border-top: 1px solid #FFE8A8;
    flex-wrap: wrap;
}
.lumak-contact-map-footer > i {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: #FFF3D6;
    color: #E5A91A;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.lumak-contact-map-footer h4 {
    font-size: 15px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 4px;
}
.lumak-contact-map-footer p {
    font-size: 13px;
    color: #666;
    margin: 0;
    line-height: 1.5;
}
.lumak-contact-map-footer a { color: #E5A91A; font-weight: 600; text-decoration: none; }
.lumak-contact-map-footer a:hover { text-decoration: underline; }

.lumak-contact-cta {
    background: #D49A0A;
    border-radius: 22px;
    padding: clamp(36px, 5vw, 48px) clamp(24px, 4vw, 40px);
    text-align: center;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.lumak-contact-cta::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 0%, transparent 50%);
    pointer-events: none;
}
.lumak-contact-cta h3 {
    font-size: clamp(1.2rem, 3vw, 1.5rem);
    font-weight: 800;
    margin-bottom: 10px;
    position: relative;
}
.lumak-contact-cta p {
    font-size: 14px;
    color: rgba(255,255,255,0.85);
    margin-bottom: 22px;
    position: relative;
}
.lumak-contact-cta-btns {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    position: relative;
}
.lumak-contact-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 24px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.lumak-contact-cta-btn.primary {
    background: #fff;
    color: #E5A91A;
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}
.lumak-contact-cta-btn.primary:hover {
    transform: translateY(-2px);
    color: #D4A017;
}
.lumak-contact-cta-btn.secondary {
    background: rgba(255,255,255,0.12);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.28);
}
.lumak-contact-cta-btn.secondary:hover {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

@media (max-width: 992px) {
    .lumak-contact-grid { grid-template-columns: 1fr; }
    .lumak-contact-help-grid { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .lumak-contact-quick { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .lumak-contact-quick-item:nth-child(2) { border-right: none; }
    .lumak-contact-quick-item:nth-child(-n+2) { border-bottom: 1px solid #FFE8A8; }
    .lumak-contact-form .form-row { grid-template-columns: 1fr; }
    .lumak-contact-btn.submit { width: 100%; }
}

@media (max-width: 480px) {
    .lumak-contact-cta-btns { flex-direction: column; align-items: stretch; }
    .lumak-contact-cta-btn { justify-content: center; }
}
</style>

<!-- Hero -->
<header class="lumak-contact-hero<?php echo $contactBannerUrl ? ' has-banner' : ''; ?>">
    <?php if ($contactBannerUrl): ?>
    <div class="lumak-contact-hero-bg" aria-hidden="true">
        <img src="<?php echo htmlspecialchars($contactBannerUrl); ?>"
             alt=""
             width="1920"
             height="520"
             loading="eager"
             fetchpriority="high"
             decoding="async">
    </div>
    <div class="lumak-contact-hero-overlay" aria-hidden="true"></div>
    <?php endif; ?>
    <div class="lumak-contact-hero-hearts" aria-hidden="true">
        <i class="fas fa-headset h1"></i>
        <i class="fas fa-envelope h2"></i>
        <i class="fas fa-comments h3"></i>
    </div>
    <div class="container">
        <div class="lumak-contact-hero-inner">
            <span class="lumak-contact-hero-badge"><i class="fas fa-headset"></i> We're Here to Help</span>
            <h1>Contact <span><?php echo SITE_NAME; ?></span></h1>
            <p class="lumak-contact-hero-lead">Questions about an order, a product, or delivery? Our team at <?php echo STORE_OWNER; ?> is ready to help you.</p>
        </div>
    </div>
</header>

<!-- Quick contact strip -->
<div class="container">
    <div class="lumak-contact-quick">
        <div class="lumak-contact-quick-item lumak-contact-quick-item--static">
            <i class="fas fa-phone-alt"></i>
            <strong>Call Us</strong>
            <span class="lumak-contact-quick-phones">
                <?php echo render_store_phone_numbers([STORE_PHONE_1, STORE_PHONE_2], [
                    'table_class' => 'store-phone-table--sm',
                    'attrs' => ['data-turbo' => 'false'],
                ]); ?>
            </span>
        </div>
        <a href="https://wa.me/<?php echo STORE_WHATSAPP; ?>" target="_blank" rel="noopener" class="lumak-contact-quick-item">
            <i class="fab fa-whatsapp"></i>
            <strong>WhatsApp</strong>
            <span>Chat instantly</span>
        </a>
        <a href="mailto:<?php echo STORE_EMAIL; ?>" class="lumak-contact-quick-item">
            <i class="fas fa-envelope"></i>
            <strong>Email</strong>
            <span><?php echo STORE_EMAIL; ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>track-order.php" class="lumak-contact-quick-item">
            <i class="fas fa-box"></i>
            <strong>Track Order</strong>
            <span>Check delivery status</span>
        </a>
    </div>
</div>

<section class="lumak-contact-body">
    <div class="container">

        <div class="lumak-contact-grid">
            <!-- Left: Contact info -->
            <div class="lumak-contact-info-stack">
                <div class="lumak-contact-info-card">
                    <div class="lumak-contact-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="lumak-contact-info-content">
                        <h3>Our Location</h3>
                        <p><a href="<?php echo htmlspecialchars(store_map_open_url()); ?>" target="_blank" rel="noopener noreferrer"><?php echo STORE_LOCATION; ?></a><br><a href="https://<?php echo STORE_DOMAIN; ?>" target="_blank" rel="noopener"><?php echo STORE_DOMAIN; ?></a></p>
                    </div>
                </div>

                <div class="lumak-contact-info-card">
                    <div class="lumak-contact-info-icon"><i class="fas fa-phone-alt"></i></div>
                    <div class="lumak-contact-info-content">
                        <h3>Phone</h3>
                        <?php echo render_store_phone_numbers([STORE_PHONE_1, STORE_PHONE_2], ['attrs' => ['data-turbo' => 'false']]); ?>
                    </div>
                </div>

                <div class="lumak-contact-info-card">
                    <div class="lumak-contact-info-icon whatsapp"><i class="fab fa-whatsapp"></i></div>
                    <div class="lumak-contact-info-content">
                        <h3>WhatsApp Support</h3>
                        <p>Fast replies for orders, products, and delivery questions.<br><a href="https://wa.me/<?php echo STORE_WHATSAPP; ?>" target="_blank" rel="noopener">Message us on WhatsApp</a></p>
                    </div>
                </div>

                <div class="lumak-contact-info-card">
                    <div class="lumak-contact-info-icon"><i class="fas fa-clock"></i></div>
                    <div class="lumak-contact-info-content">
                        <h3>Working Hours</h3>
                        <p>Monday – Saturday: 8:00 AM – 6:00 PM<br>Sunday: Closed<br><span style="font-size:12px;color:#E5A91A;">24/7 online ordering available</span></p>
                    </div>
                </div>

                <div class="lumak-contact-info-card">
                    <div class="lumak-contact-info-icon"><i class="fas fa-share-alt"></i></div>
                    <div class="lumak-contact-info-content">
                        <h3>Follow <?php echo SITE_NAME; ?></h3>
                        <p>Stay updated on new arrivals, offers &amp; collections.</p>
                        <div class="lumak-contact-social">
                            <a href="<?php echo htmlspecialchars(STORE_FACEBOOK_URL); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="<?php echo htmlspecialchars(STORE_INSTAGRAM_URL); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="<?php echo htmlspecialchars(STORE_TIKTOK_URL); ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                            <a href="<?php echo htmlspecialchars(STORE_WHATSAPP_URL); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Contact form -->
            <div class="lumak-contact-form-card">
                <div class="lumak-contact-form-header">
                    <h2><i class="fas fa-paper-plane"></i> Send Us a Message</h2>
                    <p>Fill in the form below and we'll get back to you as soon as possible.</p>
                </div>
                <div class="lumak-contact-form-body">
                    <form class="lumak-contact-form" id="lumakContactForm" novalidate data-ajax>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contactName">Full Name <span style="color:#ef4444;">*</span></label>
                                <input type="text" id="contactName" name="name" placeholder="Your full name" required>
                                <i class="fas fa-user input-icon"></i>
                            </div>
                            <div class="form-group">
                                <label for="contactPhone">Phone Number</label>
                                <input type="tel" id="contactPhone" name="phone" placeholder="07X XXX XXXX">
                                <i class="fas fa-phone-alt input-icon"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="contactEmail">Email Address</label>
                            <input type="email" id="contactEmail" name="email" placeholder="your@email.com">
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                        <p style="margin:-6px 0 14px;font-size:11px;color:#888;line-height:1.5;">Provide your email and/or phone so we can send your enquiry reference number.</p>
                        <div class="form-group">
                            <label for="contactTopic">Topic</label>
                            <select id="contactTopic" name="topic">
                                <option value="General Enquiry">General Enquiry</option>
                                <option value="Order Question">Order Question</option>
                                <option value="Shipping & Delivery">Shipping &amp; Delivery</option>
                                <option value="Returns & Refunds">Returns &amp; Refunds</option>
                                <option value="Product Question">Product Question</option>
                                <option value="Wholesale / Bulk Order">Wholesale / Bulk Order</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="contactSubject">Subject</label>
                            <input type="text" id="contactSubject" name="subject" placeholder="Brief summary of your message" required>
                            <i class="fas fa-tag input-icon"></i>
                        </div>
                        <div class="form-group">
                            <label for="contactMessage">Message</label>
                            <textarea id="contactMessage" name="message" placeholder="Tell us how we can help you... (at least 10 characters)" required></textarea>
                        </div>
                        <div class="lumak-contact-form-actions">
                            <button type="submit" class="lumak-contact-btn submit" id="contactSubmitBtn">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </div>
                        <div class="lumak-contact-alert-slot" aria-live="polite">
                            <div class="lumak-contact-alert" id="contactFormAlert" role="alert" aria-hidden="true"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="lumak-contact-success-overlay" id="contactSuccessOverlay" role="dialog" aria-modal="true" aria-labelledby="contactSuccessTitle" hidden>
            <div class="lumak-contact-success-modal">
                <div class="lumak-contact-success-head">
                    <div class="lumak-contact-success-icon"><i class="fas fa-check"></i></div>
                    <h3 id="contactSuccessTitle">Message Sent Successfully</h3>
                    <p>Thank you for reaching out to <?php echo SITE_NAME; ?>.</p>
                </div>
                <div class="lumak-contact-success-body">
                    <p class="lumak-contact-ref-label">Your Reference Number</p>
                    <div class="lumak-contact-ref-no" id="contactReferenceNo">CM-000000-0000</div>
                    <p class="lumak-contact-success-note">We have emailed your enquiry to our support team. A confirmation with this reference has been sent to you<?php echo ezymart_sms_is_configured() ? ' by email and SMS' : ' by email'; ?>.</p>
                    <div class="lumak-contact-success-sla">
                        <i class="fas fa-clock"></i>
                        <span>Expected response within <strong>1–3 working days</strong></span>
                    </div>
                    <button type="button" class="lumak-contact-success-close" id="contactSuccessClose">Done</button>
                </div>
            </div>
        </div>

        <!-- Help links -->
        <div class="lumak-contact-section-head">
            <h2>Quick Help</h2>
            <p>Find answers faster — browse our help pages before reaching out.</p>
        </div>
        <div class="lumak-contact-help-grid">
            <a href="<?php echo BASE_URL; ?>faq.php" class="lumak-contact-help-card">
                <i class="fas fa-question-circle"></i>
                <strong>FAQ</strong>
                <span>Common questions about products, shipping &amp; payments</span>
            </a>
            <a href="<?php echo BASE_URL; ?>shipping-policy.php" class="lumak-contact-help-card">
                <i class="fas fa-truck"></i>
                <strong>Shipping Policy</strong>
                <span>Delivery times, tracking &amp; island-wide shipping info</span>
            </a>
            <a href="<?php echo BASE_URL; ?>refund-policy.php" class="lumak-contact-help-card">
                <i class="fas fa-undo-alt"></i>
                <strong>Refund Policy</strong>
                <span>Returns, exchanges &amp; refund process</span>
            </a>
        </div>

        <!-- Map -->
        <div class="lumak-contact-map-wrap">
            <iframe
                src="<?php echo htmlspecialchars(store_map_embed_url()); ?>"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="<?php echo htmlspecialchars(SITE_NAME); ?> location map"></iframe>
            <div class="lumak-contact-map-footer">
                <i class="fas fa-store"></i>
                <div>
                    <h4><?php echo SITE_NAME; ?> Store</h4>
                    <p>
                        <a href="<?php echo htmlspecialchars(store_map_open_url()); ?>" target="_blank" rel="noopener noreferrer"><?php echo STORE_LOCATION; ?></a>
                        &bull; <a href="mailto:<?php echo STORE_EMAIL; ?>"><?php echo STORE_EMAIL; ?></a>
                        &bull; <a href="https://<?php echo STORE_DOMAIN; ?>" target="_blank" rel="noopener"><?php echo STORE_DOMAIN; ?></a>
                    </p>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="lumak-contact-cta">
            <h3>Ready to Explore Tech Products?</h3>
            <p>Browse our mobile and tech collections and find the right products for work, study, and everyday use.</p>
            <div class="lumak-contact-cta-btns">
                <a href="<?php echo BASE_URL; ?>shop.php" class="lumak-contact-cta-btn primary"><i class="fas fa-shopping-bag"></i> Shop Now</a>
                <a href="<?php echo BASE_URL; ?>about.php" class="lumak-contact-cta-btn secondary"><i class="fas fa-heart"></i> About ARIGATO.LK</a>
            </div>
        </div>

    </div>
</section>

<script>
(function initContactForm() {
    var form = document.getElementById('lumakContactForm');
    var submitBtn = document.getElementById('contactSubmitBtn');
    var alertEl = document.getElementById('contactFormAlert');
    var overlay = document.getElementById('contactSuccessOverlay');
    var refEl = document.getElementById('contactReferenceNo');
    var closeBtn = document.getElementById('contactSuccessClose');

    if (!form || !submitBtn) return;

    var submitBtnLabel = '<i class="fas fa-paper-plane"></i> Send Message';
    var isSubmitting = false;
    var messageField = form.querySelector('[name="message"]');

    function resetSubmitButton() {
        isSubmitting = false;
        if (form.dataset) {
            delete form.dataset.submitting;
        }
        submitBtn.disabled = false;
        submitBtn.removeAttribute('disabled');
        submitBtn.removeAttribute('aria-busy');
        submitBtn.classList.remove('is-loading');
        submitBtn.style.opacity = '';
        submitBtn.style.cursor = '';
        submitBtn.style.pointerEvents = '';
        submitBtn.innerHTML = submitBtnLabel;
    }

    function setSubmitLoading() {
        isSubmitting = true;
        submitBtn.classList.add('is-loading');
        submitBtn.setAttribute('aria-busy', 'true');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    }

    function validateContactForm() {
        var nameField = form.querySelector('[name="name"]');
        var subjectField = form.querySelector('[name="subject"]');
        var emailField = form.querySelector('[name="email"]');
        var phoneField = form.querySelector('[name="phone"]');
        var name = nameField ? nameField.value.trim() : '';
        var subject = subjectField ? subjectField.value.trim() : '';
        var email = emailField ? emailField.value.trim() : '';
        var phone = phoneField ? phoneField.value.trim() : '';
        var message = messageField ? messageField.value.trim() : '';

        if (!name) {
            return { message: 'Please enter your full name.', field: nameField };
        }
        if (!subject) {
            return { message: 'Please enter a subject for your message.', field: subjectField };
        }
        if (!email && !phone) {
            return { message: 'Please enter your email address or phone number so we can send your reference.', field: emailField || phoneField };
        }
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            return { message: 'Please enter a valid email address.', field: emailField };
        }
        if (message.length < 10) {
            return { message: 'Please provide a little more detail in your message (at least 10 characters).', field: messageField };
        }
        return null;
    }

    function showAlert(message, focusField) {
        if (!alertEl) return;
        alertEl.textContent = message;
        alertEl.setAttribute('aria-hidden', 'false');
        alertEl.classList.add('is-error', 'is-visible');
        if (focusField && typeof focusField.focus === 'function') {
            focusField.focus();
        }
    }

    function hideAlert() {
        if (!alertEl) return;
        alertEl.textContent = '';
        alertEl.setAttribute('aria-hidden', 'true');
        alertEl.classList.remove('is-error', 'is-visible');
    }

    function openSuccessOverlay(referenceNo) {
        if (!overlay || !refEl) return;
        refEl.textContent = referenceNo;
        overlay.hidden = false;
        requestAnimationFrame(function() {
            overlay.classList.add('is-visible');
        });
        document.body.style.overflow = 'hidden';
    }

    function closeSuccessOverlay() {
        if (!overlay) return;
        overlay.classList.remove('is-visible');
        document.body.style.overflow = '';
        resetSubmitButton();
        setTimeout(function() {
            overlay.hidden = true;
        }, 280);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSuccessOverlay);
    }
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeSuccessOverlay();
        });
    }

    if (messageField) {
        messageField.addEventListener('input', function() {
            if (isSubmitting) return;
            resetSubmitButton();
            if (messageField.value.trim().length >= 10 && alertEl && alertEl.classList.contains('is-visible')) {
                hideAlert();
            }
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideAlert();

        if (isSubmitting) {
            return;
        }

        var validationError = validateContactForm();
        if (validationError) {
            resetSubmitButton();
            showAlert(validationError.message, validationError.field);
            return;
        }

        var email = form.querySelector('[name="email"]').value.trim();
        var phone = form.querySelector('[name="phone"]').value.trim();

        setSubmitLoading();

        var body = new FormData(form);
        body.append('action', 'submit_contact');

        fetch('<?php echo BASE_URL; ?>contact-api.php', {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) {
            return r.text().then(function(text) {
                var data = {};
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (err) {
                    data = { success: false, message: 'Unexpected server response. Please try again.' };
                }
                return data;
            });
        })
        .then(function(data) {
            if (data.success && data.reference_no) {
                form.reset();
                resetSubmitButton();
                openSuccessOverlay(data.reference_no);
                return;
            }
            var errMsg = data.message || 'Could not send your message. Please try again.';
            var focusEl = errMsg.toLowerCase().indexOf('message') !== -1 ? messageField : null;
            showAlert(errMsg, focusEl);
        })
        .catch(function() {
            showAlert('Something went wrong. Please try again or contact us by phone.');
        })
        .finally(function() {
            resetSubmitButton();
        });
    });
})();
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
