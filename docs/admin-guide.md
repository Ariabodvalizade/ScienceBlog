# Administrator & Editor Guide

For the journal team: Journal Manager, Editor-in-Chief and Section Editors. It covers day-to-day work in the OJS 3.5
backend and the features specific to this website. Server tasks are covered in [deployment-vps.md](deployment-vps.md).

## 1. Accounts and panels

| Who | Where | What they see |
|---|---|---|
| Site administrator | `/index/admin` | Server-level settings, journals, system information, cache clearing |
| Journal Manager / Editor | *Dashboard* (user menu) | All submissions, issues, settings, users, statistics |
| Section Editor | *Dashboard* | Submissions assigned to them |
| Reviewer | *Dashboard* | Review invitations and assignments |
| Author | *Dashboard › My Submissions as Author* | Their submissions and statuses: active, revisions requested, scheduled, published, declined |

**How authors join:**
1. They register (*Register* in the header). New accounts get the *Reader* role.
2. They click *Submit a Manuscript*. Starting the first submission automatically gives them the *Author* role and the
   *My Submissions* dashboard.
3. In *Profile › Public* they can upload a **profile photo** (cropped to 150×150 px) and add a **bio** and a
   **homepage URL**. These appear on their public author page (§6).

### The Home page

Everyone who logs in lands on **Home** (the first item in the left menu, `/[journal]/workspace`). Every number on it
is a link to the matching list.

- **Editors and managers** see:
  - **Needs your attention:** new submissions without an editor, submissions ready for a decision, revisions
    received, overdue reviews
  - **Publishing pipeline:** how many submissions are in each stage, from Submission to Published
  - recent submissions with their status, the current issue, and shortcuts to common tasks (create an issue, post an
    announcement, website settings, users, statistics)
- **Authors** see:
  - their submissions with a plain status (Draft, Submitted, In review, Copyediting, Production, Published, Declined).
    Drafts open the submission form so they can finish it.
  - a **New submission** button
  - a checklist for their public profile (photo, biography, affiliation, ORCID, website) with a link to their author
    page
- **Reviewers** see a banner when review requests are waiting.

The admin panel uses the journal's accent colour (theme option, §7), and *View website* in the header opens the public
site. On a phone, the menu sits above the page.

> Authors **submit**; editors **publish**. An article appears on the website only after peer review and after an editor
> schedules it into an issue. Indexing services require this peer-review step.

## 2. The editorial workflow

```
Submission ──► Review ──► Copyediting ──► Production ──► Scheduled in an issue ──► Published
   (screen)    (reviewers,     (language)     (PDF + optional
               decisions)                     HTML galley)
```

1. **Submission:** a new manuscript appears in *Dashboard › Unassigned*. Assign a section editor (*Assign* in the
   Participants panel), check scope and anonymisation, then *Send to Review* or *Decline*.
2. **Review** (double-anonymous by default):
   - *Add Reviewer*: search existing reviewers or create one. Set the response and review due dates (defaults: 7 / 21
     days). OJS sends automatic reminders.
   - When the reviews are in, record a decision: *Accept*, *Request Revisions*, *Resubmit for Review*, or *Decline*.
3. **Copyediting:** assign a copyeditor, or upload the final version yourself.
4. **Production:** upload **galleys** in *Publication › Galleys*:
   - **PDF** (required). Use a text-based PDF with the title and authors on the first page; Google Scholar needs this.
   - **HTML** (optional, recommended). The site then shows the **full text inline** on the article page, with clickable
     citations. See §5.
5. **Publish:** in *Publication*, check Title & Abstract, Contributors, Metadata (keywords), References (paste the
   reference list, one per line), License and Issue. Then *Schedule For Publication* into an issue.
6. **Issue:** *Issues › Future Issues › [issue] › Publish Issue*. All scheduled articles in it go live together and DOIs
   are deposited.

## 3. Issues

- *Issues › Create Issue*: volume, number, year, optional title and description, and a **cover image** (portrait,
  about 600×800 px, which the site uses on the home, issue, archive and article pages).
- Mark the newest published issue as **Current**; the home page shows it.
- *Issue galley* (optional): a PDF of the whole issue, shown as a button on the issue page.

## 4. References and linking

- Paste each article's reference list in *Publication › References*, one reference per line. Include the DOI
  (`https://doi.org/10.xxxx/…` or `doi:10.xxxx/…`) whenever it exists.
- The website then:
  - numbers the references and links every DOI and URL
  - adds **Google Scholar** and **Crossref** look-up links to each reference
  - deposits the references to Crossref with the DOI (when Crossref is configured)
- **Keywords** become clickable chips that search the journal.
- You can hyperlink any word in an abstract with the rich-text editor's link button.

## 5. HTML full text (optional, per article)

Upload an `.html` file as a galley labelled **Full Text**. Images go in as *dependent files* of that galley: upload
them from the galley's file panel and keep the same filenames used in the HTML.

Write the HTML simply:

```html
<h2>1. Introduction</h2>
<p>Heat stress reduces milk yield [1, 2]. Earlier work found similar effects [3–5].</p>
<h3>2.1. Animals</h3>
<table><caption>Table 1. …</caption> … </table>
```

- **Headings** `h2`/`h3` build the article's *On this page* menu automatically.
- **Citations**: numbered `[1]`, `[1, 2]`, `[3–5]`, or author–year `(Smith et al., 2020; Lee and Park, 2019)`. They
  become links to the reference list, and hovering over them shows the reference.
- If the HTML contains its own "References" section, the site hides it; the reference list comes from the metadata
  (§4).
- The HTML is sanitised: scripts, styles and embedded frames are removed.

Word users: *Save as › Web Page, Filtered*, then remove the title, author list and abstract; the page already shows
them.

## 6. Author pages

- `/authors` lists everyone who has published in the journal. Each author has a page at `/authors/view/…` with their
  photo, affiliation, ORCID, website, bio and all of their articles. On article pages, author names link to these pages.
- The same person is recognised across articles by **ORCID iD** (best), otherwise by **e-mail address**. Ask authors to
  add their ORCID iD to every submission.
- The photo, bio and website come from the author's own account (*Profile › Public*). Co-authors without an account
  get a page with initials and the bio entered on the submission.
- Pages update automatically when articles are published or edited, within one hour at most.

## 7. Website settings

| Task | Where |
|---|---|
| Theme options: accent colour, home page sections, tagline, indexing logos | *Settings › Website › Appearance › Theme* |
| Logo, favicon, footer text | *Settings › Website › Appearance › Setup* |
| Menus: header and drawer | *Settings › Website › Setup › Navigation* (primary menu). Add *Authors* as a Remote URL `https://[domain]/[journal]/authors` |
| Policy pages (Aims & Scope, Journal Policies, Indexing, …) | *Settings › Website › Static Pages* |
| Announcements | *Settings › Website › Setup › Announcements*, then *Announcements* in the left menu |
| About, Author Guidelines, Submission checklist | *Settings › Journal* and *Settings › Workflow › Submission* |
| E-mail templates and signature | *Settings › Workflow › Emails* |
| DOIs and Crossref | *Settings › Distribution › DOIs* (see [ojs-configuration.md §5](ojs-configuration.md)) |

**Indexing logos format** (theme option), one per line: `Name | logo image URL | link URL`. Upload logo images
(SVG/PNG) through a static page or the logo uploader first, then paste their URLs.

## 8. Users and roles

- *Users & Roles › Users*: search, edit, assign roles, merge duplicate accounts, and send a password reset.
- **Editorial masthead**: in *Users & Roles › Roles*, roles marked *Show on masthead* (Journal editor, Section editor,
  Editorial Board Member) appear on *About › Editorial Team*. Add the board members' affiliation and ORCID to their
  profiles.
- Reviewers: *Users & Roles › Users › Add User*, role *Reviewer*, with reviewing interests.

## 9. Housekeeping

- **Statistics:** *Statistics › Articles / Editorial Activity / Users*.
- **Clear caches** after changing theme options, if pages look stale: *Administration › Delete Data Caches* and
  *Delete Template Cache*.
- **Backups** run nightly on the server. Ask the administrator to test a restore every few months.
- **Before launch**: delete the demo journal and test accounts, and change every password used during testing.
