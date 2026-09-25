# Indexing Readiness Guide

> **Disclaimer.** The contractor prepares the website technically and gives guidance. Several databases require a
> publishing history, a minimum number of articles, regular issues, editorial quality or other conditions before
> they review or accept a new journal. **No acceptance or indexing is guaranteed.** The final decision rests solely
> with each database. Criteria change over time: verify each database's current requirements on its official site
> when applying.

Legend: 🛠 = provided by the website build (technical) · 📝 = content or policy the client must supply · ⏳ = requires
publishing history

## 0. Foundations (needed by all indexes)

| Item | Type | Where |
|---|---|---|
| ISSN (online; print if any) registered, and displayed in the header or footer and on every article page | 📝 → 🛠 | Masthead; Meridian footer + JSON-LD |
| Stable, clean article URLs; one landing page per article | 🛠 | `restful_urls = On` |
| DOIs for every article (Crossref) | 📝 credentials → 🛠 | DOI plugin + Crossref |
| English title, abstract and keywords for every article | 📝 | Submission metadata (required) |
| References in Roman script, with DOIs where available | 📝 → 🛠 | Required metadata; reference linking |
| Aims & Scope, Editorial Board (with affiliations and ORCID), Author Guidelines, Peer-review policy, Publication ethics (COPE), Plagiarism policy, Open access statement, License (CC BY 4.0), Copyright, APC statement (even if "no charges"), Archiving policy, Contact page | 📝 → 🛠 | Static pages + masthead; templates provided |
| License shown on every article page and in the PDF | 🛠 + 📝 | Meridian article page; PDF template |
| Regular publication schedule, stated publicly, e.g. quarterly | 📝 ⏳ | About page |
| Digital preservation (PKP PN / LOCKSS) | 🛠 (needs ISSN) | PKP PN plugin |
| OAI-PMH endpoint | 🛠 | `/oai` |

## 1. Google Scholar (crawled automatically; there is no application)

Technical checklist, all delivered by the build 🛠:
- [ ] Each article has its own HTML landing page with the title, authors and **abstract** visible without login.
- [ ] `citation_title`, `citation_author`, `citation_publication_date`, `citation_journal_title`, `citation_issn`,
      `citation_volume`, `citation_issue`, `citation_firstpage`, `citation_doi` and `citation_pdf_url` meta tags
      (Google Scholar plugin).
- [ ] The PDF is freely downloadable from the `citation_pdf_url` (no interstitial, no login).
- [ ] PDFs are **text-based** (not scanned images), ideally < 5 MB, with the **title and authors on the first page**. 📝 production
- [ ] Browse paths reach every article: Archives → Issue → Article; `/sitemap` is available.
- [ ] `robots.txt` allows crawling of article, issue and PDF URLs.
- [ ] No pop-ups or cookie walls that block content.

After launch: publish the first issue, wait for crawling (typically several weeks). If articles still don't appear,
use Google Scholar's publisher support/inclusion form.

## 2. DOAJ (Directory of Open Access Journals)

- 🛠 Website: every policy page listed in §0, CC license on articles, OA statement, DOAJ export plugin (for later metadata uploads).
- 📝 Journal: ISSN confirmed on the ISSN Portal; journal title matching the ISSN record; editorial board of ≥ 2
  (DOAJ expects more) with full affiliations; clear peer-review description; APC info; no embargo.
- ⏳ History: DOAJ expects the journal to have an established publishing record. Its criteria have required either
  **a year or more of publishing history or a minimum number of published research articles** (historically 10).
  Check the current "Basic criteria for inclusion" before applying.
- Apply via the DOAJ website (publisher account → application form). The review usually takes several months.

## 3. EBSCO

- 📝 Contact EBSCO's publisher relations (title suggestion / partnership form). They consider academic relevance,
  peer review, English abstracts, ISSN and regular publication.
- 🛠 OAI-PMH endpoint and full-text PDFs are available for their harvesting.
- ⏳ Usually expects several published issues.

## 4. CABI (CAB Abstracts / Global Health) — relevant for veterinary, animal and applied life sciences

- 📝 Submit the journal through CABI's journal suggestion/evaluation contact. Scope must fit CABI's subject coverage.
- 🛠 English abstracts, stable URLs and DOIs, plus OAI.
- ⏳ Regular issues and a demonstrable publishing record.

## 5. Scopus (Elsevier) — evaluated by the Content Selection & Advisory Board (CSAB)

Minimum eligibility (as published by Scopus; verify before applying):
- Peer-reviewed content with a publicly available peer-review process description 📝
- **Published on a regular basis**, with a publishing history — Scopus has asked for **at least 2 years** of history ⏳
- Registered ISSN 📝
- English titles and abstracts; references in Roman script 📝
- A publicly available publication ethics and malpractice statement 📝

Evaluation criteria: journal policy (editorial concept, peer review, editor and author geographic diversity),
content quality, **citedness** of the journal's articles in Scopus, regularity (no delays), and online availability
(full website, English homepage, quality 🛠). Apply through the Scopus title suggestion form.

## 6. Web of Science (Clarivate) — ESCI first

- Clarivate evaluates all journals against **24 quality criteria**. ESCI entry is based on these; SCIE/SSCI also
  apply 4 impact criteria.
  - Initial triage: ISSN, title, publisher, URL, content access, peer-review policy, contact details, timeliness.
  - Editorial triage: scholarly content, English article titles and abstracts, bibliographic information in Roman
    script, language clarity, timeliness and publication volume, website functionality, ethics statement,
    editorial affiliation and author affiliation details.
  - Editorial quality: editorial board composition, validity of statements, peer review, content relevance, grant
    support details, adherence to community standards, author distribution, appropriate citations to the literature.
- 🛠 The website covers the functional and presentational criteria; 📝 ⏳ the editorial ones require time and content.
- Submit via the Clarivate journal submission portal. Journals are advised to show consistent, on-time issues first.

## 7. Suggested timeline (from the first issue)

| When | Action |
|---|---|
| Before launch | ISSN, Crossref membership, all policy pages, editorial board complete |
| Launch | Google Scholar-ready site live; OAI endpoint; DOIs on every article |
| 3–6 months | Check Google Scholar coverage; register with PKP PN; ORCID in all author records |
| 6–12 months | DOAJ application (once the history/article thresholds are met); EBSCO and CABI suggestions |
| 24+ months | Scopus application; Clarivate (ESCI) submission when regularity is proven |
