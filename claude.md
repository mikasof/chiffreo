1. Règles fondamentales de design
• Jamais de look 'généré par IA'

INTERDIT : les dégradés génériques bleu-violet, les cards avec border-radius excessif, les ombres trop prononcées, les mises en page centrées sans hiérarchie visuelle.
INTERDIT : utiliser Inter + Lucide comme combo par défaut. Ce sont les marqueurs instantanés d'une UI générée par IA.
OBLIGATOIRE : proposer des polices alternatives depuis Google Fonts (ex : Instrument Sans, Satoshi, General Sans, Plus Jakarta Sans, Manrope, Geist).
OBLIGATOIRE : utiliser des bibliothèques d'icônes distinctives (Phosphor Icons, Heroicons, Radix Icons) plutôt que Lucide systématiquement.

2. Design System avant tout
Avant d'écrire la moindre ligne de code UI, tu DOIS définir et respecter le DESIGN_SYSTEM (colors, typography, spacing, radius, shadows).
Si je fournis un mood board, une capture d'écran ou une palette, tu DOIS en extraire les couleurs et adapter le design system en conséquence.

3. Hiérarchie visuelle obligatoire
Pour chaque page ou composant, applique systématiquement :
Contraste typographique : minimum 3 tailles de texte différentes visibles (titre, sous-titre, corps)
Espacement intentionnel : plus d'espace = plus d'importance. Les sections principales ont plus de padding que les sous-sections
Points focaux : chaque section a UN élément qui attire l'œil en premier (CTA, titre, image)
Rythme vertical : alterner les sections denses et aérées
4. Règles anti-patterns
Tu ne fais JAMAIS :
Des boutons tous de la même taille/couleur sur une même page
Des textes centrés partout — le centrage est réservé aux hero sections et aux CTA
Des cards identiques en grille sans variation de taille ou de mise en avant
Du texte blanc sur fond clair ou du texte gris clair sur fond blanc
Des sections sans espacement suffisant entre elles
Des animations gratuites
Processus de travail
Capture d'écran/wireframe : Reproduis la mise en page fidèlement avant d'ajouter quoi que ce soit. Ne réinterprète pas.
Mood board/référence : Extrais la palette dominante, identifie le style typographique, note le niveau de contraste.
Sans référence : Demande-moi un exemple ou propose 2-3 approches visuelles différentes. Ne code jamais une UI 'par défaut'.
Stack technique préférée
Framework : React + TypeScript
Styling : Tailwind CSS
Composants : shadcn/ui (toujours customiser les styles)
Animations : Framer Motion, CSS transitions
Icônes : Phosphor Icons ou Heroicons
Polices : Toujours proposer une alternative à Inter
Patterns de code UI
// Toujours typer les props
interface ComponentProps {
  variant?: 'primary' | 'secondary' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
}

Responsive Design & Accessibilité
Mobile-first, pas de scroll horizontal, touch targets min 44x44px.
Contraste WCAG AA, aria-label, focus visible, alt sur les images.
Checklist avant livraison
Vérifie que le design system est respecté, la hiérarchie est claire, le responsive fonctionne, l'accessibilité est assurée, et que ça ne ressemble PAS à un template générique.