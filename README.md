# stake-bonus-20.com

Site statique (HTML/CSS/JS) + 2 petites fonctions Vercel pour le formulaire de contact et l'admin.
Aucune compilation nécessaire : Vercel sert les fichiers tels quels.

## Mettre en ligne (GitHub → Vercel)

1. **GitHub** : crée un nouveau dépôt (privé conseillé), puis *Add file → Upload files*.
   Glisse **tout le contenu** de ce dossier (pas le dossier lui-même) : `index.html`, `assets/`, `api/`, `vercel.json`… doivent être à la racine du dépôt. Clique *Commit changes*.
2. **Vercel** : *Add New → Project → Import* ton dépôt GitHub.
   - Framework Preset : **Other**
   - Root Directory : `./`
   - Build Command / Output Directory : **vides**
   - Clique *Deploy*.
3. **Base de données (pour le formulaire et l'admin)** : dans le projet Vercel → **Storage** → *Create* → **Upstash for Redis** (gratuit) → *Connect* au projet. Les variables sont ajoutées automatiquement.
4. **Mot de passe admin** : *Settings → Environment Variables* → ajoute `ADMIN_PASSWORD` (10 caractères minimum).
5. **Redéploie** (*Deployments → … → Redeploy*) pour que les variables soient prises en compte.
6. **Domaine** : *Settings → Domains* → ajoute `stake-bonus-20.com` (et `www.stake-bonus-20.com` en redirection vers la version sans www). Suis les instructions DNS que Vercel affiche chez ton registraire.
7. **Google** : Search Console → ajoute le site → soumets `https://stake-bonus-20.com/sitemap.xml`.

## Après la mise en ligne

- Admin : `https://stake-bonus-20.com/admin/` (mot de passe = `ADMIN_PASSWORD`).
- À remplir toi-même (texte entre crochets `[ ]`) :
  - `impressum/index.html` : société, adresse, n° C-XXXXX, directeur, e-mail
  - `datenschutz/index.html` : mêmes infos (Vercel et Upstash sont déjà indiqués ; choisis la région **EU** en créant la base Upstash)
- Pour modifier une page plus tard : édite le fichier sur GitHub, Vercel redéploie tout seul.
