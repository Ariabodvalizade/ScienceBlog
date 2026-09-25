// Demo content for development and design review ONLY.
// Articles, authors and affiliations are fictional. The references are real,
// widely cited methodological papers so that DOI links resolve correctly.

export const journal = {
  name: 'Demo Journal of Animal Science',
  acronym: 'DJAS',
  abbreviation: 'Demo J. Anim. Sci.',
  path: 'djas',
  onlineIssn: '0000-0000',
  publisher: 'Demo Academic Publishing',
  contactName: 'Editorial Office',
  contactEmail: 'editor@example.org',
  tagline: 'Peer-reviewed · Open access · Animal and veterinary science',
  description:
    '<p>An international, peer-reviewed, open access journal publishing original research, reviews and case reports across animal and veterinary science — from animal health and welfare to production, nutrition and laboratory animal medicine.</p>',
  about:
    '<p>The <em>Demo Journal of Animal Science</em> is a placeholder journal used to design and test this website. All articles, authors and affiliations shown are fictional.</p><p>The journal follows a double-anonymous peer-review process, publishes under the Creative Commons Attribution 4.0 licence and charges no fees to read.</p>',
};

export const sections = [
  { ref: 'ART', title: 'Original Research', seq: 1 },
  { ref: 'REV', title: 'Review Article', seq: 2 },
  { ref: 'SC', title: 'Short Communication', seq: 3 },
  { ref: 'CR', title: 'Case Report', seq: 4 },
];

export const refs = {
  arrive1:
    'Kilkenny C, Browne WJ, Cuthill IC, Emerson M, Altman DG. Improving bioscience research reporting: the ARRIVE guidelines for reporting animal research. PLoS Biol. 2010;8(6):e1000412. https://doi.org/10.1371/journal.pbio.1000412',
  arrive2:
    'Percie du Sert N, Hurst V, Ahluwalia A, Alam S, Avey MT, Baker M, et al. The ARRIVE guidelines 2.0: updated guidelines for reporting animal research. PLoS Biol. 2020;18(7):e3000410. https://doi.org/10.1371/journal.pbio.3000410',
  festing:
    'Festing MFW, Altman DG. Guidelines for the design and statistical analysis of experiments using laboratory animals. ILAR J. 2002;43(4):244–258. https://doi.org/10.1093/ilar.43.4.244',
  gpower:
    'Faul F, Erdfelder E, Lang AG, Buchner A. G*Power 3: a flexible statistical power analysis program for the social, behavioral, and biomedical sciences. Behav Res Methods. 2007;39(2):175–191. https://doi.org/10.3758/BF03193146',
  lme4:
    'Bates D, Mächler M, Bolker B, Walker S. Fitting linear mixed-effects models using lme4. J Stat Softw. 2015;67(1):1–48. https://doi.org/10.18637/jss.v067.i01',
  imagej:
    'Schneider CA, Rasband WS, Eliceiri KW. NIH Image to ImageJ: 25 years of image analysis. Nat Methods. 2012;9(7):671–675. https://doi.org/10.1038/nmeth.2089',
  landis:
    'Landis JR, Koch GG. The measurement of observer agreement for categorical data. Biometrics. 1977;33(1):159–174. https://doi.org/10.2307/2529310',
  bh:
    'Benjamini Y, Hochberg Y. Controlling the false discovery rate: a practical and powerful approach to multiple testing. J R Stat Soc Series B Stat Methodol. 1995;57(1):289–300. https://doi.org/10.1111/j.2517-6161.1995.tb02031.x',
  prisma:
    'Page MJ, McKenzie JE, Bossuyt PM, Boutron I, Hoffmann TC, Mulrow CD, et al. The PRISMA 2020 statement: an updated guideline for reporting systematic reviews. BMJ. 2021;372:n71. https://doi.org/10.1136/bmj.n71',
  syrcle:
    'Hooijmans CR, Rovers MM, de Vries RBM, Leenaars M, Ritskes-Hoitinga M, Langendam MW. SYRCLE’s risk of bias tool for animal studies. BMC Med Res Methodol. 2014;14:43. https://doi.org/10.1186/1471-2288-14-43',
  russell:
    'Russell WMS, Burch RL. The Principles of Humane Experimental Technique. London: Methuen; 1959.',
  rcore:
    'R Core Team. R: A language and environment for statistical computing. Vienna: R Foundation for Statistical Computing; 2025. https://www.R-project.org/',
};

// Josiah Carberry is ORCID's official fictitious test researcher.
const CARBERRY_ORCID = 'https://orcid.org/0000-0002-1825-0097';

export const issues = [
  {
    volume: 1,
    number: 1,
    year: 2026,
    title: '',
    datePublished: '2026-03-15',
    current: false,
    description: '<p>The inaugural issue: nutrition, udder health, equine welfare and feline anaesthesia.</p>',
    articles: [
      {
        section: 'ART',
        title: 'Effects of dietary probiotic supplementation on growth performance and intestinal morphology in broiler chickens',
        pages: '1–9',
        dateSubmitted: '2025-11-02',
        authors: [
          { given: 'Emily', family: 'Carter', affiliation: 'Department of Animal Science, Northfield University', country: 'GB', bio: 'Emily Carter is an assistant professor of poultry nutrition whose work focuses on feed additives and gut health.' },
          { given: 'Daniel', family: 'Hughes', affiliation: 'Institute of Poultry Research, Eastbrook College', country: 'GB' },
          { given: 'Rachel', family: 'Morgan', affiliation: 'Department of Animal Science, Northfield University', country: 'IE' },
        ],
        keywords: ['broiler', 'probiotic', 'Bacillus subtilis', 'villus height', 'feed conversion ratio'],
        abstract:
          '<p><strong>Background:</strong> Probiotics are increasingly used as alternatives to antibiotic growth promoters in poultry production.</p><p><strong>Methods:</strong> A total of 480 one-day-old Ross 308 broilers were randomly allocated to four dietary treatments (0, 0.5, 1.0 and 1.5 g/kg of a <em>Bacillus subtilis</em> probiotic) with six replicate pens each for 42 days.</p><p><strong>Results:</strong> Supplementation at 1.0 g/kg improved body weight gain by 6.8% and the feed conversion ratio by 5.1% compared with the control (P &lt; 0.05). Villus height and villus-to-crypt ratio in the jejunum increased linearly with the dose.</p><p><strong>Conclusions:</strong> Dietary <em>B. subtilis</em> at 1.0 g/kg is an effective strategy to support growth and intestinal integrity in broilers.</p>',
        references: ['arrive2', 'festing', 'gpower', 'lme4', 'imagej', 'rcore'],
      },
      {
        section: 'ART',
        title: 'Prevalence of subclinical mastitis and associated risk factors in smallholder dairy herds',
        pages: '10–18',
        dateSubmitted: '2025-10-18',
        authors: [
          { given: 'Andrew', family: 'Collins', affiliation: 'Faculty of Veterinary Medicine, Northfield University', country: 'GB' },
          { given: 'Grace', family: 'Whitfield', affiliation: 'Department of Clinical Studies, Riverside Veterinary School', country: 'US' },
        ],
        keywords: ['dairy cattle', 'subclinical mastitis', 'California mastitis test', 'risk factors', 'smallholder'],
        abstract:
          '<p><strong>Objective:</strong> To estimate the prevalence of subclinical mastitis (SCM) and identify herd- and cow-level risk factors in smallholder dairy farms.</p><p><strong>Methods:</strong> A cross-sectional study sampled 612 lactating cows from 94 herds. Quarters were screened with the California mastitis test and positive samples were cultured.</p><p><strong>Results:</strong> Cow-level SCM prevalence was 38.4% (95% CI 34.6–42.3). Parity ≥ 4, late lactation and the absence of post-milking teat dipping were associated with higher odds of SCM. <em>Staphylococcus aureus</em> was the most frequent isolate.</p><p><strong>Conclusions:</strong> Simple hygiene interventions could substantially reduce SCM in smallholder systems.</p>',
        references: ['landis', 'lme4', 'bh', 'rcore'],
      },
      {
        section: 'SC',
        title: 'Welfare assessment of working equids using a modified hands-on protocol',
        pages: '19–23',
        dateSubmitted: '2025-12-01',
        authors: [
          { given: 'Sophie', family: 'Bennett', affiliation: 'Centre for Animal Welfare, Eastbrook College', country: 'GB' },
          { given: 'Robert', family: 'Hayes', affiliation: 'Faculty of Veterinary Medicine, Northfield University', country: 'US' },
        ],
        keywords: ['animal welfare', 'donkey', 'working equids', 'body condition score'],
        abstract:
          '<p>We adapted a hands-on welfare protocol for working donkeys and mules and tested its inter-observer reliability in 120 animals. Agreement was substantial for body condition score (κ = 0.71) and lesion scoring (κ = 0.66). The protocol can be completed in under ten minutes per animal and is suitable for routine field surveillance.</p>',
        references: ['landis', 'russell', 'arrive1'],
      },
      {
        section: 'ART',
        title: 'Comparison of two anaesthetic protocols for ovariohysterectomy in cats: a randomized blinded trial',
        pages: '24–32',
        dateSubmitted: '2025-09-27',
        authors: [
          { given: 'Josiah', family: 'Carberry', affiliation: 'Department of Surgery, Riverside Veterinary School', country: 'US', orcid: CARBERRY_ORCID, bio: 'Josiah Carberry is ORCID’s fictitious test researcher, used here to demonstrate the ORCID badge.' },
          { given: 'Hannah', family: 'Brooks', affiliation: 'Department of Surgery, Riverside Veterinary School', country: 'CA' },
          { given: 'Oliver', family: 'Reed', affiliation: 'Faculty of Veterinary Medicine, Northfield University', country: 'GB' },
        ],
        keywords: ['feline', 'anaesthesia', 'alfaxalone', 'propofol', 'ovariohysterectomy'],
        abstract:
          '<p><strong>Objective:</strong> To compare recovery quality and cardiorespiratory variables between alfaxalone- and propofol-based induction protocols in cats undergoing elective ovariohysterectomy.</p><p><strong>Methods:</strong> Sixty healthy cats were randomly assigned to receive either protocol; assessors were blinded to treatment.</p><p><strong>Results:</strong> Recovery scores and times to extubation did not differ between groups. Post-induction apnoea was less frequent with alfaxalone (7% vs 27%).</p><p><strong>Conclusions:</strong> Both protocols are suitable; alfaxalone may be preferred where respiratory depression is a concern.</p>',
        references: ['arrive2', 'festing', 'gpower', 'bh'],
      },
    ],
  },
  {
    volume: 1,
    number: 2,
    year: 2026,
    title: '',
    datePublished: '2026-06-15',
    current: true,
    description: '<p>This issue brings together work on heat stress in dairy cattle, antimicrobial resistance surveillance, laboratory rabbit reference intervals and emergency surgery in large-breed dogs.</p>',
    articles: [
      {
        section: 'ART',
        title: 'Heat stress modulates rumen fermentation and milk yield in Holstein dairy cows: a randomized crossover study',
        pages: '33–44',
        dateSubmitted: '2026-02-11',
        html: true,
        authors: [
          { given: 'Sarah', family: 'Mitchell', affiliation: 'Department of Animal Science, Northfield University', country: 'GB', bio: 'Sarah Mitchell is an associate professor of dairy science. Her research examines how climate affects the nutrition, health and productivity of dairy cattle.' },
          { given: 'Josiah', family: 'Carberry', affiliation: 'Department of Surgery, Riverside Veterinary School', country: 'US', orcid: CARBERRY_ORCID },
          { given: 'Eleanor', family: 'Price', affiliation: 'Institute of Animal Physiology, Eastbrook College', country: 'GB' },
          { given: 'Matthew', family: 'Jenkins', affiliation: 'Department of Animal Science, Northfield University', country: 'AU' },
        ],
        keywords: ['heat stress', 'dairy cow', 'rumen fermentation', 'temperature–humidity index', 'milk yield', 'volatile fatty acids'],
        abstract:
          '<p><strong>Background:</strong> Rising ambient temperatures threaten the productivity and welfare of high-yielding dairy cows.</p><p><strong>Methods:</strong> Sixteen multiparous Holstein cows were enrolled in a randomized crossover design with two 21-day periods: thermoneutral (temperature–humidity index, THI &lt; 68) and heat stress (THI 78–82). Rumen fluid, milk yield and composition, and physiological variables were measured.</p><p><strong>Results:</strong> Heat stress reduced dry matter intake by 12% and milk yield by 3.9 kg/day (P &lt; 0.01). Total volatile fatty acid concentration decreased while ruminal pH and the acetate-to-propionate ratio increased.</p><p><strong>Conclusions:</strong> Heat stress impairs rumen fermentation independently of intake, supporting nutritional strategies that target rumen function during hot seasons.</p>',
        references: ['arrive2', 'festing', 'gpower', 'lme4', 'bh', 'imagej', 'rcore', 'russell'],
      },
      {
        section: 'REV',
        title: 'Antimicrobial resistance in companion animals: a systematic review of surveillance data (2015–2025)',
        pages: '45–60',
        dateSubmitted: '2026-01-20',
        authors: [
          { given: 'Natalie', family: 'Foster', affiliation: 'Department of Microbiology, Northfield University', country: 'US' },
          { given: 'Thomas', family: 'Walker', affiliation: 'Institute of Veterinary Public Health, Eastbrook College', country: 'GB' },
        ],
        keywords: ['antimicrobial resistance', 'dogs', 'cats', 'surveillance', 'systematic review', 'One Health'],
        abstract:
          '<p><strong>Objective:</strong> To summarise surveillance data on antimicrobial resistance (AMR) in bacterial isolates from dogs and cats published between 2015 and 2025.</p><p><strong>Methods:</strong> Following PRISMA 2020, four databases were searched; 87 studies met the inclusion criteria and risk of bias was assessed.</p><p><strong>Results:</strong> Resistance to aminopenicillins was common in <em>Escherichia coli</em> (pooled 41%), and methicillin resistance was reported in 7–19% of <em>Staphylococcus pseudintermedius</em> isolates. Reporting standards varied widely.</p><p><strong>Conclusions:</strong> Harmonised surveillance of companion animals is needed within a One Health framework.</p>',
        references: ['prisma', 'syrcle', 'bh', 'rcore'],
      },
      {
        section: 'SC',
        title: 'Serum biochemistry reference intervals for adult laboratory rabbits (Oryctolagus cuniculus) housed under enriched conditions',
        pages: '61–66',
        dateSubmitted: '2026-03-03',
        authors: [
          { given: 'Alexander', family: 'Turner', affiliation: 'Laboratory Animal Centre, Northfield University', country: 'NZ' },
          { given: 'Claire', family: 'Donovan', affiliation: 'Laboratory Animal Centre, Northfield University', country: 'IE' },
        ],
        keywords: ['laboratory animals', 'rabbit', 'reference intervals', 'clinical biochemistry', 'environmental enrichment'],
        abstract:
          '<p>Reference intervals for 18 serum biochemistry analytes were established from 124 clinically healthy New Zealand White rabbits housed with environmental enrichment. Intervals for glucose and creatine kinase were narrower than published values from barren housing, suggesting reduced handling stress. These data support the refinement of laboratory rabbit studies.</p>',
        references: ['russell', 'arrive2', 'festing'],
      },
      {
        section: 'CR',
        title: 'Successful surgical management of gastric dilatation–volvulus in a 9-year-old Great Dane',
        pages: '67–70',
        dateSubmitted: '2026-02-26',
        authors: [
          { given: 'Katherine', family: 'Adams', affiliation: 'Small Animal Teaching Hospital, Riverside Veterinary School', country: 'CA' },
        ],
        keywords: ['dog', 'gastric dilatation–volvulus', 'gastropexy', 'emergency surgery'],
        abstract:
          '<p>A 9-year-old male Great Dane presented with acute abdominal distension and non-productive retching. Radiography confirmed gastric dilatation–volvulus. After stabilisation and gastric decompression, derotation and incisional gastropexy were performed. The dog recovered without complications and remained healthy at the six-month follow-up.</p>',
        references: ['russell'],
      },
    ],
  },
];

// Fictional editorial board (dev/demo only). Group names are OJS defaults.
// Manuscripts still in the editorial workflow (not published), so the admin
// panel's Home page, pipeline and Submissions lists show realistic work.
// `stage`: submission | editorial (copyediting) | production.
// `editor`: a board member assigned as editor (username), if any.
export const inProgress = [
  {
    section: 'ART',
    stage: 'submission',
    dateSubmitted: '2026-09-22',
    title: 'Early detection of lameness in dairy cows from accelerometer data: a machine-learning approach',
    authors: [
      { given: 'Sarah', family: 'Mitchell', affiliation: 'Department of Animal Science, Northfield University', country: 'GB' },
      { given: 'Thomas', family: 'Walker', affiliation: 'School of Computing, Eastbrook College', country: 'GB' },
    ],
    keywords: ['lameness', 'dairy cow', 'accelerometer', 'machine learning', 'precision livestock farming'],
    abstract: '<p>Lameness is among the most costly welfare problems in dairy herds, yet it is often detected late. We trained gradient-boosted models on leg-mounted accelerometer data from 212 Holstein cows and compared their predictions with weekly locomotion scores. The best model identified lame cows a median of 4 days before visual scoring, with a sensitivity of 0.84 and a specificity of 0.91.</p>',
  },
  {
    section: 'SC',
    stage: 'submission',
    dateSubmitted: '2026-09-18',
    title: 'Dietary seaweed supplementation and enteric methane emissions in grazing sheep',
    authors: [
      { given: 'Oliver', family: 'Reed', affiliation: 'Centre for Sustainable Livestock, Westmoor University', country: 'GB' },
      { given: 'Hannah', family: 'Brooks', affiliation: 'Department of Animal Nutrition, Lakeside Agricultural College', country: 'CA' },
    ],
    keywords: ['methane', 'sheep', 'seaweed', 'Asparagopsis', 'grazing'],
    abstract: '<p>We measured enteric methane in 48 grazing ewes receiving 0%, 0.5% or 1% dietary <em>Asparagopsis</em> for 60 days. Methane yield fell by 31% at the highest inclusion rate without affecting live weight gain.</p>',
  },
  {
    section: 'ART',
    stage: 'editorial',
    dateSubmitted: '2026-06-30',
    editor: 'margaret.ellison',
    title: 'Seroprevalence of Toxoplasma gondii in free-range backyard chickens and associated household risk factors',
    authors: [
      { given: 'Natalie', family: 'Foster', affiliation: 'Department of Microbiology, Northfield University', country: 'US' },
      { given: 'Robert', family: 'Hayes', affiliation: 'State Veterinary Diagnostic Laboratory', country: 'US' },
    ],
    keywords: ['Toxoplasma gondii', 'chickens', 'seroprevalence', 'One Health'],
    abstract: '<p>Free-range chickens are sentinels for environmental contamination with <em>Toxoplasma gondii</em> oocysts. Of 540 chickens from 96 households, 18.3% were seropositive by modified agglutination test. Cat ownership and a soil floor in the coop were associated with seropositivity.</p>',
  },
  {
    section: 'CR',
    stage: 'production',
    dateSubmitted: '2026-05-12',
    editor: 'margaret.ellison',
    title: 'Surgical management of a congenital portosystemic shunt in a young alpaca',
    authors: [
      { given: 'Claire', family: 'Donovan', affiliation: 'Riverside Veterinary School', country: 'IE' },
    ],
    keywords: ['alpaca', 'portosystemic shunt', 'camelid surgery', 'case report'],
    abstract: '<p>A 4-month-old alpaca presented with ill thrift and neurological signs. Computed tomography confirmed a single extrahepatic shunt, which was attenuated with an ameroid constrictor. The cria was clinically normal 6 months after surgery.</p>',
  },
];

export const board = [
  { given: 'Margaret', family: 'Ellison', group: 'Journal editor', affiliation: 'Faculty of Veterinary Medicine, Northfield University', country: 'GB', bio: 'Professor of veterinary epidemiology.' },
  { given: 'James', family: 'O’Connor', group: 'Section editor', affiliation: 'School of Agriculture and Food Science, Eastbrook College', country: 'IE' },
  { given: 'Alice', family: 'Thompson', group: 'Section editor', affiliation: 'Department of Surgery, Riverside Veterinary School', country: 'US' },
  { given: 'Lucas', family: 'Fletcher', group: 'Editorial Board Member', affiliation: 'Department of Animal Nutrition, Southgate University', country: 'AU' },
  { given: 'Amelia', family: 'Dawson', group: 'Editorial Board Member', affiliation: 'Institute of Livestock Research, Westfield University', country: 'GB' },
  { given: 'Henry', family: 'Marshall', group: 'Editorial Board Member', affiliation: 'Institute of Animal Welfare, Eastbrook College', country: 'GB' },
  { given: 'Philippa', family: 'Nash', group: 'Editorial Board Member', affiliation: 'Department of Veterinary Microbiology, Northfield University', country: 'NZ' },
];

// Static pages. The policy texts are TEMPLATES for the client to adapt.
export const staticPages = [
  {
    path: 'aims-and-scope',
    title: 'Aims & Scope',
    content: `<p>The <em>[Journal Name]</em> publishes original research, reviews, short communications and case reports across animal and veterinary science.</p>
<h2>Scope</h2>
<ul><li>Animal health, disease and epidemiology</li><li>Animal welfare and behaviour</li><li>Nutrition, production and reproduction</li><li>Laboratory animal science and the 3Rs</li><li>Public health and One Health</li></ul>
<h2>Article types</h2>
<p>Original Research, Review Article, Short Communication and Case Report. See the <a href="about/submissions">Author Guidelines</a> for requirements.</p>`,
  },
  {
    path: 'policies',
    title: 'Journal Policies',
    content: `<p class="lead">These policies describe how the journal handles manuscripts, publication ethics, access and preservation. [Template — the editorial office should review every section before launch.]</p>
<h2 id="peer-review">Peer review process</h2>
<p>All research articles undergo double-anonymous peer review by at least two independent reviewers. The handling editor screens submissions for scope and quality before review; the Editor-in-Chief makes the final decision. Target time to first decision: [X] weeks.</p>
<h2 id="open-access">Open access</h2>
<p>The journal provides immediate open access to its content on the principle that making research freely available to the public supports a greater global exchange of knowledge. There are no subscription or pay-per-view charges.</p>
<h2 id="ethics">Publication ethics and malpractice</h2>
<p>The journal follows the Core Practices of the Committee on Publication Ethics (COPE). Studies involving animals must state approval by an institutional animal ethics committee and comply with the ARRIVE guidelines; studies involving humans require informed consent and ethics approval.</p>
<h2 id="plagiarism">Plagiarism</h2>
<p>Submissions are screened with similarity-checking software. Manuscripts with substantial overlap with published work are declined; suspected misconduct is handled according to COPE flowcharts.</p>
<h2 id="copyright">Copyright and licensing</h2>
<p>Authors retain copyright. Articles are published under the Creative Commons Attribution 4.0 International License (CC BY 4.0), which permits use, sharing and adaptation with appropriate credit.</p>
<h2 id="archiving">Archiving</h2>
<p>The journal content is preserved through the PKP Preservation Network (PKP PN). [Add any national library deposit.]</p>
<h2 id="apc">Article processing charges</h2>
<p>[State the APC amount, or: “The journal charges no submission or publication fees.”]</p>
<h2 id="corrections">Corrections, retractions and expressions of concern</h2>
<p>Errors are corrected through published corrections linked to the original article. Retractions follow COPE guidance and remain openly available with a clear retraction notice.</p>
<h2 id="conflicts">Conflicts of interest</h2>
<p>Authors, reviewers and editors must disclose any financial or personal relationships that could influence their work. Editors do not handle manuscripts where they have a conflict of interest.</p>
<h2 id="data">Data sharing</h2>
<p>Authors are encouraged to deposit data in a public repository and to include a data availability statement.</p>
<h2 id="ai">Use of AI tools</h2>
<p>AI tools cannot be listed as authors. Any use of generative AI in preparing a manuscript must be disclosed in the methods or acknowledgements; authors remain responsible for the content.</p>
<h2 id="complaints">Complaints and appeals</h2>
<p>Appeals against editorial decisions and complaints should be sent to the editorial office at [email]; they are handled by an editor not involved in the original decision.</p>`,
  },
  {
    path: 'indexing',
    title: 'Indexing & Abstracting',
    content: `<p>The journal is technically prepared for academic indexing services. Current status:</p>
<ul><li>Google Scholar — metadata provided for every article</li><li>Crossref — DOIs registered for all articles</li><li>ORCID — author identifiers integrated</li><li>PKP Preservation Network — archiving</li><li>DOAJ — application planned once eligibility criteria are met</li></ul>
<p>OAI-PMH endpoint for harvesters: <code>/oai</code></p>`,
  },
];

export const announcements = [
  {
    title: 'Call for papers: Heat stress and animal welfare',
    short: '<p>We invite submissions for a thematic collection on climate, heat stress and animal welfare. Deadline: 31 December 2026.</p>',
  },
  {
    title: 'The journal is now accepting submissions',
    short: '<p>Our online submission system is open. Please read the Author Guidelines before submitting your manuscript.</p>',
  },
];
