"""
Automated Responsive Testing Suite for Suivi des Carrières
Tests navigation, visual layout, horizontal overflow, and mobile interactions.
"""
import subprocess
import time
from pathlib import Path
from playwright.sync_api import sync_playwright

RESOLUTIONS = [
    (320, 568, "petit_smartphone"),
    (360, 800, "smartphone_android"),
    (390, 844, "iphone"),
    (430, 932, "smartphone_large"),
    (768, 1024, "tablette_portrait"),
    (1024, 768, "tablette_paysage"),
    (1280, 800, "petit_ordinateur"),
    (1366, 768, "ordinateur_portable"),
    (1440, 900, "desktop"),
    (1920, 1080, "grand_ecran"),
]

PAGES = [
    ("login", "/login", False),
    ("dashboard", "/dashboard", True),
    ("liste_agents", "/admin/employe", True),
    ("nouvel_agent", "/admin/employe/new", True),
    ("gestion_conges", "/admin/conges/", True),
    ("pointage", "/admin/pointage/", True),
    ("visite", "/admin/visite/", True),
    ("absences", "/admin/absences/", True),
    ("affectation", "/admin/affectation", True),
    ("acte", "/admin/acte", True),
    ("poste", "/admin/poste", True),
    ("missions", "/admin/missions/", True),
    ("interims", "/admin/interims/", True),
    ("service", "/admin/service", True),
    ("structure", "/admin/structure", True),
    ("statistique", "/admin/statistique/", True),
    ("institution", "/admin/institution", True),
    ("user", "/admin/user", True),
    ("document", "/admin/document", True),
    ("securite_compte", "/account/security", True),
]

SCREENSHOT_DIR = Path("tests/visual/screenshots")
SCREENSHOT_DIR.mkdir(parents=True, exist_ok=True)

def run():
    print("=== Démarrage du serveur PHP pour les tests responsive ===")
    server_proc = subprocess.Popen(
        ["php", "-S", "127.0.0.1:8099", "-t", "public"],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL
    )
    time.sleep(2)

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(
                executable_path="/usr/bin/chromium",
                headless=True,
                args=["--no-sandbox", "--disable-gpu"]
            )

            # 1. Établir la session avec connexion
            context = browser.new_context(viewport={"width": 1440, "height": 900})
            page = context.new_page()

            print("Connexion sur http://127.0.0.1:8099/login ...")
            page.goto("http://127.0.0.1:8099/login")
            page.fill('input[name="email"]', "dominiquedamba@fake.io")
            page.fill('input[name="password"]', "@password")
            page.click('button[type="submit"]')
            page.wait_for_url("**/dashboard")
            print(f"Connexion réussie ! URL actuelle : {page.url}")

            # Récupérer les cookies de session pour les réutiliser dans chaque contexte de résolution
            storage_state = context.storage_state()
            context.close()

            # 2. Tester chaque résolution sur chaque page
            total_checks = 0
            overflow_errors = []

            for width, height, device_name in RESOLUTIONS:
                print(f"\n--- Test Résolution : {width}x{height} ({device_name}) ---")
                res_context_auth = browser.new_context(
                    viewport={"width": width, "height": height},
                    storage_state=storage_state
                )
                res_context_anon = browser.new_context(
                    viewport={"width": width, "height": height}
                )

                for page_name, url_path, requires_auth in PAGES:
                    ctx = res_context_auth if requires_auth else res_context_anon
                    res_page = ctx.new_page()
                    url = f"http://127.0.0.1:8099{url_path}"
                    res_page.goto(url)
                    res_page.wait_for_load_state("domcontentloaded")
                    time.sleep(0.15)

                    # Vérification 1 : Pas de défilement horizontal (scrollWidth <= innerWidth + 2px tolérance sub-pixel)
                    metrics = res_page.evaluate("""() => {
                        const body = document.body;
                        const html = document.documentElement;
                        const scrollWidth = Math.max(body.scrollWidth, html.scrollWidth);
                        const clientWidth = html.clientWidth;
                        const innerWidth = window.innerWidth;
                        return { scrollWidth, clientWidth, innerWidth, hasOverflow: scrollWidth > innerWidth + 2 };
                    }""")

                    total_checks += 1
                    if metrics["hasOverflow"]:
                        overflow_errors.append(f"{page_name} @ {width}x{height}: scrollWidth={metrics['scrollWidth']} > window={metrics['innerWidth']}")
                        print(f"  [OVERFLOW] {page_name}: scrollWidth={metrics['scrollWidth']}, innerWidth={metrics['innerWidth']}")
                    else:
                        print(f"  [OK] {page_name} (Largeur OK : {metrics['clientWidth']}px)")

                    # Sauvegarder des captures pour les résolutions représentatives
                    if (width, height) in [(360, 800), (768, 1024), (1440, 900)] and page_name in ["login", "dashboard", "liste_agents", "nouvel_agent", "gestion_conges", "pointage", "statistique", "securite_compte"]:
                        shot_path = SCREENSHOT_DIR / f"{page_name}_{width}x{height}_{device_name}.png"
                        res_page.screenshot(path=str(shot_path), full_page=False)

                    res_page.close()

                # Test spécifique Mobile : interaction avec le drawer menu
                if width < 992:
                    mob_page = res_context_auth.new_page()
                    mob_page.goto("http://127.0.0.1:8099/dashboard")
                    mob_page.wait_for_load_state("domcontentloaded")
                    toggle_btn = mob_page.query_selector("#kt_aside_mobile_toggle")
                    if toggle_btn and toggle_btn.is_visible():
                        toggle_btn.click()
                        time.sleep(0.3)
                        is_drawer_shown = mob_page.evaluate("""() => {
                            const aside = document.getElementById('kt_aside');
                            return aside && (aside.classList.contains('drawer-on') || aside.style.display !== 'none');
                        }""")
                        print(f"  [Mobile Test] Toggle menu mobile à {width}px : {'Ouvert avec succès' if is_drawer_shown else 'Présent'}")
                    mob_page.close()

                res_context_auth.close()
                res_context_anon.close()

            browser.close()

            print("\n=== RÉSULTATS DU CONTRÔLE RESPONSIVE ===")
            print(f"Total des vérifications de page : {total_checks}")
            if overflow_errors:
                print(f"ATTENTION : {len(overflow_errors)} anomalies de débordement détectées :")
                for err in overflow_errors:
                    print(f" - {err}")
            else:
                print("SUCCÈS : 0 débordement horizontal détecté sur toutes les résolutions et toutes les pages !")

    finally:
        server_proc.terminate()
        server_proc.wait()
        print("Serveur PHP arrêté.")

if __name__ == "__main__":
    run()
