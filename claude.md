1. Règles fondamentales de design
• Jamais de look 'généré par IA'

INTERDIT : les dégradés génériques bleu-violet, les cards avec border-radius excessif, les ombres trop prononcées, les mises en page centrées sans hiérarchie visuelle.
INTERDIT : utiliser Inter + Lucide comme combo par défaut. Ce sont les marqueurs instantanés d'une UI générée par IA.
OBLIGATOIRE : proposer des polices alternatives depuis Google Fonts (ex : Instrument Sans, Satoshi, General Sans, Plus Jakarta Sans, Manrope, Geist).
OBLIGATOIRE : utiliser des bibliothèques d'icônes distinctives (Phosphor Icons, Heroicons, Radix Icons) plutôt que Lucide systématiquement.

2. Design System avant tout
Avant d'écrire la moindre ligne de code UI, tu DOIS définir et respecter le DESIGN_SYSTEM (colors, typography, spacing, radius, shadows).
Si je fournis un mood board, une capture d'écran ou une palette, tu DOIS en extraire les couleurs et adapter le design system en conséquence.

3. Hiérarchie visuelle obligatoire
Pour chaque page ou composant, applique systématiquement :
Contraste typographique : minimum 3 tailles de texte différentes visibles (titre, sous-titre, corps)
Espacement intentionnel : plus d'espace = plus d'importance. Les sections principales ont plus de padding que les sous-sections
Points focaux : chaque section a UN élément qui attire l'œil en premier (CTA, titre, image)
Rythme vertical : alterner les sections denses et aérées

4. Règles anti-patterns
Tu ne fais JAMAIS :
Des boutons tous de la même taille/couleur sur une même page
Des textes centrés partout — le centrage est réservé aux hero sections et aux CTA
Des cards identiques en grille sans variation de taille ou de mise en avant
Du texte blanc sur fond clair ou du texte gris clair sur fond blanc
Des sections sans espacement suffisant entre elles
Des animations gratuites
Processus de travail
Capture d'écran/wireframe : Reproduis la mise en page fidèlement avant d'ajouter quoi que ce soit. Ne réinterprète pas.
Mood board/référence : Extrais la palette dominante, identifie le style typographique, note le niveau de contraste.
Sans référence : Demande-moi un exemple ou propose 2-3 approches visuelles différentes. Ne code jamais une UI 'par défaut'.
Stack technique préférée
Framework : React + TypeScript
Styling : Tailwind CSS
Composants : shadcn/ui (toujours customiser les styles)
Animations : Framer Motion, CSS transitions
Icônes : Phosphor Icons ou Heroicons
Polices : Toujours proposer une alternative à Inter
Patterns de code UI
// Toujours typer les props
interface ComponentProps {
  variant?: 'primary' | 'secondary' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
}

Responsive Design & Accessibilité
Mobile-first, pas de scroll horizontal, touch targets min 44x44px.
Contraste WCAG AA, aria-label, focus visible, alt sur les images.
Checklist avant livraison
Vérifie que le design system est respecté, la hiérarchie est claire, le responsive fonctionne, l'accessibilité est assurée, et que ça ne ressemble PAS à un template générique.

---

## 5. Architecture et Routage Local/Production

### Structure des environnements
- **Local (MAMP)** : `http://localhost:8888/chiffreo/` avec `APP_BASE_PATH=/chiffreo`
- **Production (o2switch)** : `https://chiffreo.fr/` avec `APP_BASE_PATH=` (vide)

### Règles OBLIGATOIRES pour tout développement

#### Chemins dans les fichiers HTML
- TOUJOURS utiliser des chemins **relatifs** (sans `/` initial) : `css/app.css`, `js/config.js`, `icons/logo.png`
- JAMAIS de chemins absolus `/css/...` ou `/js/...`
- Le tag `<base href>` est injecté dynamiquement par `index.php` selon l'environnement

#### Liens internes (navigation)
- Les liens `<a href="/settings">` sont corrigés par le script "link fixer" en JS
- Ce script ajoute `BASE_PATH` aux liens commençant par `/` en local

#### Fichiers à NE JAMAIS synchroniser entre local et prod
- `.env` (configuration différente par environnement)
- `.htaccess` (RewriteBase différent : `/chiffreo/` en local, `/` en prod)

#### Fichiers OK à synchroniser
- `index.php` (gère le routage et l'injection du `<base>` tag)
- Tous les `.html` (utilisent des chemins relatifs)
- `js/`, `css/`, `icons/`, `src/`, `config/`, `vendor/`

### Routes principales
| URL | Page | Description |
|-----|------|-------------|
| `/` | index.html | Application principale |
| `/accueil` | landing.html | Page marketing |
| `/auth` | auth.html | Connexion/inscription |
| `/settings` | settings.html | Paramètres compte |
| `/onboarding` | onboarding.html | Onboarding utilisateur |

### Valeurs par défaut métier
- Taux horaire main d'œuvre : **70€/h**
- Marge fournitures : 20%
- Déplacement : 30€ forfait
