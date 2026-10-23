# Couleurs Expernet - analyse et propositions

Analyse du 22 octobre 2026. Source : CSS et médias publics de https://expernet.re/.

Le bleu `#009DE0` et l’orange `#ED8F00` sont explicitement présents dans les styles du site. Ces valeurs constituent le socle retenu, sans prétendre remplacer une charte graphique officielle qui n’a pas été fournie. Les logos téléchargés conservent leurs couleurs d’origine.

Le bleu officiel sur blanc atteint **3.04:1**. Le blanc sur orange atteint **2.46:1**. Ces associations ne conviennent pas au petit texte au seuil indicatif de 4,5:1. Nous réservons le bleu vif aux accents et utilisons un bleu plus profond pour les liens ; les boutons orange portent du texte foncé.

## Les quatre propositions

| Palette | Bleu de lecture | Bleu nuit | Orange | Fond | Surface |
|---|---|---|---|---|---|
| 01 · Signature | #0078A9 | #12333D | #ED8F00 | #F7F7F1 | #E5F3FA |
| 02 · Océan | #007AA8 | #07354A | #F0A029 | #F4F8FA | #DFEEF4 |
| 03 · Lumière | #006F9E | #173847 | #FFB547 | #FFFCF5 | #EAF6FA |
| 04 · Expertise | #006E9D | #10283F | #DE8500 | #F3F6FA | #E3EDF5 |

### 01 · Signature - Fidèle & équilibrée

La couleur Expernet au premier plan, des fonds crème et un bleu de lecture plus profond. La piste recommandée pour conserver le caractère de marque.

Contrastes calculés : texte sur blanc **13.40:1**, bleu de lecture sur blanc **4.93:1**, texte sur orange **5.44:1**.

### 02 · Océan - Calme & ancrée

Une nuance de bleu plus marine, des gris bleutés et un orange légèrement adouci. Une ambiance apaisée, en lien avec les territoires de l’océan Indien.

Contrastes calculés : texte sur blanc **12.99:1**, bleu de lecture sur blanc **4.83:1**, texte sur orange **6.04:1**.

### 03 · Lumière - Chaleureuse & accessible

Des fonds ivoire, un orange solaire et des touches cyan. Une piste plus accueillante, adaptée aux parcours de reconversion et de découverte.

Contrastes calculés : texte sur blanc **12.40:1**, bleu de lecture sur blanc **5.57:1**, texte sur orange **7.06:1**.

### 04 · Expertise - Structurée & professionnelle

Un bleu nuit plus affirmé, un orange ambré et des fonds gris froids. Une piste plus institutionnelle, orientée formations techniques et entreprises.

Contrastes calculés : texte sur blanc **15.03:1**, bleu de lecture sur blanc **5.65:1**, texte sur orange **5.34:1**.

## Recommandation

**Océan - Calme & ancrée** est la piste retenue par le propriétaire. Signature : elle garde l’orange officiel et la famille de bleu initiale, tout en améliorant les contrastes. **Océan** convient à une ambiance plus calme, **Lumière** à une approche plus chaleureuse, **Expertise** à une expression plus institutionnelle.

La page `/palettes/` permet d’appliquer chaque palette au prototype. Le choix est conservé uniquement dans le navigateur avec `localStorage` et n’est transmis à aucun service. Les logos ne sont pas recolorés.

Les ratios suivent la luminance relative sRGB. Ils ne constituent pas une certification d’accessibilité de l’ensemble des composants et des pages.
