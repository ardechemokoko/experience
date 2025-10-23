# 🗺️ Diagramme des Flux Complets - Auto-École

## 📊 Architecture Globale

```
┌─────────────────────────────────────────────────────────────────────┐
│                        SYSTÈME AUTO-ÉCOLE                           │
│                                                                     │
│  ┌──────────────┐      ┌──────────────┐      ┌──────────────┐    │
│  │   CANDIDAT   │      │ RESPONSABLE  │      │    ADMIN     │    │
│  │      👨‍🎓      │      │      🏫      │      │     👨‍💼      │    │
│  └──────┬───────┘      └──────┬───────┘      └──────┬───────┘    │
│         │                     │                      │            │
│         └─────────────────────┴──────────────────────┘            │
│                               │                                    │
│                               ▼                                    │
│                   ┌───────────────────────┐                       │
│                   │    AUTHENTIK IAM      │                       │
│                   │   Authentification    │                       │
│                   └───────────┬───────────┘                       │
│                               │                                    │
│                               ▼                                    │
│                   ┌───────────────────────┐                       │
│                   │    LARAVEL API        │                       │
│                   │    53 Endpoints       │                       │
│                   └───────────┬───────────┘                       │
│                               │                                    │
│                               ▼                                    │
│                   ┌───────────────────────┐                       │
│                   │   BASE DE DONNÉES     │                       │
│                   │   MySQL (8 tables)    │                       │
│                   └───────────────────────┘                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🎯 Flux Candidat (Détaillé)

```
┌─────────────────────────────────────────────────────────────────────┐
│                    PARCOURS CANDIDAT                                │
└─────────────────────────────────────────────────────────────────────┘

START
  │
  ▼
┌──────────────────────┐
│ 1. INSCRIPTION       │  POST /api/auth/register
│ Créer un compte      │  → Authentik + DB
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ 2. CONNEXION         │  POST /api/auth/login-direct
│ Obtenir un token     │  → Token d'accès
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ 3. PROFIL CANDIDAT   │  POST /api/candidats/complete-profile
│ Compléter infos      │  → Numéro auto: CAN-2025-XXX
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ 4. CHOISIR AUTO-ÉCOLE│  GET /api/auto-ecoles?statut=true
│ Voir la liste        │  → Liste des auto-écoles actives
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ 5. VOIR FORMATIONS   │  GET /api/auto-ecoles/{id}/formations
│ Formations dispo     │  → Permis A, B, C, etc.
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ 6. DOCS REQUIS       │  GET /api/formations/{id}/documents-requis
│ Liste obligatoire    │  → CNI, Photo, Certificat...
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ 7. S'INSCRIRE        │  POST /api/candidats/inscription-formation
│ Choisir formation    │  → Dossier créé (statut: en_attente)
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ 8. UPLOAD DOCS       │  POST /api/dossiers/{id}/upload-document
│ Pour chaque doc      │  → Document 1, 2, 3...
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ 9. SUIVRE DOSSIER    │  GET /api/candidats/mes-dossiers
│ Voir le statut       │  → en_attente, en_cours, valide
└──────┬───────────────┘
       │
       ▼
      END
```

---

## 🏫 Flux Auto-École (Détaillé)

```
┌─────────────────────────────────────────────────────────────────────┐
│                 PARCOURS RESPONSABLE AUTO-ÉCOLE                     │
└─────────────────────────────────────────────────────────────────────┘

START
  │
  ▼
┌──────────────────────┐
│ 1. CONNEXION         │  POST /api/auth/login-direct
│ En tant que resp.    │  → Token + role: responsable_auto_ecole
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ 2. DASHBOARD         │  GET /api/auto-ecoles/mes-dossiers
│ Voir stats + liste   │  → Total: 15, En attente: 5, etc.
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ 3. FILTRER           │  GET /api/auto-ecoles/mes-dossiers
│ Par statut           │      ?statut=en_attente
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ 4. EXAMINER DOSSIER  │  GET /api/dossiers/{id}
│ Détails complets     │  → Candidat + Documents
└──────┬───────────────┘
       │
       ▼
┌──────────────────────────────────┐
│ 5. VÉRIFIER DOCUMENTS            │
│ Pour chaque document :           │
│                                  │
│  ┌────────────────────────────┐ │
│  │ Document conforme ?        │ │
│  └──┬──────────────────┬──────┘ │
│     │ OUI              │ NON    │
│     ▼                  ▼        │
│  POST /valider     POST /valider│
│  valide:true       valide:false │
│                                  │
└────────┬─────────────────────────┘
         │
         ▼
┌──────────────────────────────────┐
│ 6. DÉCISION FINALE               │
│                                  │
│  ┌────────────────────────────┐ │
│  │ Tous docs OK ?             │ │
│  └──┬──────────────────┬──────┘ │
│     │ OUI              │ NON    │
│     ▼                  ▼        │
│  POST /valider     POST /valider│
│  statut:valide     statut:rejete│
│                                  │
└────────┬─────────────────────────┘
         │
         ▼
┌──────────────────────┐
│ 7. NOTIFICATION      │  (À implémenter)
│ Informer candidat    │  → Email ou notification
└──────────────────────┘
         │
         ▼
        END
```

---

## 🔄 États du Dossier

```
┌─────────────────────────────────────────────────────────────┐
│                  CYCLE DE VIE D'UN DOSSIER                  │
└─────────────────────────────────────────────────────────────┘

    ┌──────────────┐
    │ CRÉATION     │  POST /candidats/inscription-formation
    └──────┬───────┘
           │
           ▼
    ┌──────────────┐
    │ EN_ATTENTE   │  📝 Dossier créé, en attente de vérification
    └──────┬───────┘
           │
           ├─────────────────────┐
           │                     │
           ▼                     ▼
    ┌──────────────┐      ┌──────────────┐
    │  EN_COURS    │      │   REJETE     │  ❌ Dossier incomplet
    └──────┬───────┘      └──────────────┘
           │                     │
           │                     │ (Candidat corrige)
           │                     │
           │ ◄───────────────────┘
           │
           ▼
    ┌──────────────┐
    │   VALIDE     │  ✅ Dossier accepté
    └──────────────┘
           │
           ▼
    Formation commence
```

---

## 📊 Matrice de Permissions

| Action | Candidat | Responsable | Admin |
|--------|----------|-------------|-------|
| S'inscrire | ✅ | ❌ | ✅ |
| Voir ses dossiers | ✅ | ✅ (son auto-école) | ✅ (tous) |
| Upload document | ✅ (ses dossiers) | ❌ | ✅ |
| Valider document | ❌ | ✅ (son auto-école) | ✅ |
| Valider dossier | ❌ | ✅ (son auto-école) | ✅ |
| Créer formation | ❌ | ✅ | ✅ |
| Gérer référentiels | ❌ | ❌ | ✅ |

---

## 🎨 Interfaces Suggérées

### Dashboard Candidat

```
┌────────────────────────────────────────┐
│  👨‍🎓 MON ESPACE CANDIDAT               │
├────────────────────────────────────────┤
│  Profil: Jean Dupont                   │
│  N° Candidat: CAN-2025-001             │
│                                        │
│  📁 MES DOSSIERS (2)                   │
│  ┌──────────────────────────────────┐ │
│  │ Excellence Conduite - Permis B   │ │
│  │ Statut: ✅ VALIDÉ                │ │
│  │ Documents: 4/4 validés           │ │
│  │ [Voir détails]                   │ │
│  └──────────────────────────────────┘ │
│                                        │
│  ┌──────────────────────────────────┐ │
│  │ Permis Réussite - Permis A       │ │
│  │ Statut: 🔄 EN COURS              │ │
│  │ Documents: 2/3 validés           │ │
│  │ ⚠️ Photo d'identité rejetée      │ │
│  │ [Upload nouveau document]        │ │
│  └──────────────────────────────────┘ │
└────────────────────────────────────────┘
```

### Dashboard Auto-École

```
┌────────────────────────────────────────┐
│  🏫 EXCELLENCE CONDUITE                │
├────────────────────────────────────────┤
│  📊 STATISTIQUES                       │
│  En attente: 5 │ En cours: 7          │
│  Validés: 2    │ Rejetés: 1           │
│                                        │
│  🔔 DOSSIERS EN ATTENTE (5)           │
│  ┌──────────────────────────────────┐ │
│  │ CAN-2025-001 - Jean Dupont       │ │
│  │ Formation: Permis B              │ │
│  │ Documents: 3/4 uploadés          │ │
│  │ ⏰ Reçu le: 23/10/2025           │ │
│  │ [Examiner] [Valider] [Rejeter]   │ │
│  └──────────────────────────────────┘ │
└────────────────────────────────────────┘
```

---

## 🎯 Points Clés du Système

### Automatisations

1. **Numéro Candidat** : Généré automatiquement (CAN-YYYY-XXX)
2. **Création Dossier** : Automatique lors de l'inscription
3. **Statistiques** : Calculées en temps réel
4. **Date Modification** : Mise à jour automatique

### Validations

1. **Profil déjà complété** : Empêche les doublons
2. **Déjà inscrit** : Empêche double inscription même formation
3. **Formation inactive** : Empêche inscription
4. **Dossier appartenance** : Vérifie propriété avant upload/validation

### Sécurité

1. **Token requis** : Pour toutes les actions sensibles
2. **Vérification rôle** : Auto-école vs Candidat vs Admin
3. **Logging complet** : Toutes les actions tracées
4. **Commentaires obligatoires** : En cas de rejet

---

## 🌐 Swagger Mis à Jour

Accédez à la documentation complète :

```
http://localhost:8000/api/documentation
```

**Nouvelles catégories visibles** :
- 👨‍🎓 Candidats (8 endpoints) - +3 nouveaux
- 🏫 Auto-Écoles (7 endpoints) - +2 nouveaux
- 📁 Dossiers (7 endpoints) - +2 nouveaux
- 📄 Documents (6 endpoints) - +1 nouveau

**Total : 53 endpoints documentés !**

---

**🎉 Système complet de gestion candidat + validation auto-école opérationnel ! 🚀**

