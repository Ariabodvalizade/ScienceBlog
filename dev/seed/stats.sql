-- Demo usage statistics (dev only; never run in production).
-- Fills the core statistics tables with plausible numbers for the demo
-- journal's published articles: the last 13 months, ~30 countries, so the
-- Readership page, the public "Readers around the world" section and the core
-- Statistics pages have something to show. Dates are relative to today.
-- Applied by dev/seed.sh after demo.sql; safe to re-run.

SET @ctx = (SELECT journal_id FROM journals WHERE path = 'djas');

DELETE FROM metrics_submission WHERE context_id = @ctx;
DELETE FROM metrics_context WHERE context_id = @ctx;
DELETE FROM metrics_issue WHERE context_id = @ctx;
DELETE FROM metrics_counter_submission_daily WHERE context_id = @ctx;
DELETE FROM metrics_counter_submission_monthly WHERE context_id = @ctx;
DELETE FROM metrics_submission_geo_daily WHERE context_id = @ctx;
DELETE FROM metrics_submission_geo_monthly WHERE context_id = @ctx;

DROP TEMPORARY TABLE IF EXISTS demo_dates, demo_articles, demo_countries;

-- One row per day for the last 30 days, then the 15th of each older month
-- (one row stands for that month), with a growth factor: the journal is newer
-- in the past.
CREATE TEMPORARY TABLE demo_dates (d DATE, span INT, g DECIMAL(5,3));
INSERT INTO demo_dates
  WITH RECURSIVE r(n) AS (SELECT 0 UNION ALL SELECT n + 1 FROM r WHERE n < 29)
  SELECT CURDATE() - INTERVAL n DAY, 1, 1 FROM r;
INSERT INTO demo_dates
  WITH RECURSIVE r(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM r WHERE n < 13)
  SELECT d, 30, 1 - n * .045 FROM (
    SELECT n, STR_TO_DATE(DATE_FORMAT(CURDATE() - INTERVAL n MONTH, '%Y-%m-15'), '%Y-%m-%d') AS d FROM r
  ) m WHERE d < CURDATE() - INTERVAL 29 DAY;

-- Published articles, each with a popularity weight, and their first galley
CREATE TEMPORARY TABLE demo_articles (submission_id BIGINT, w DECIMAL(5,3), galley_id BIGINT, file_id BIGINT);
INSERT INTO demo_articles
  SELECT s.submission_id, .6 + (CRC32(CONCAT('a', s.submission_id)) % 140) / 100,
    (SELECT g.galley_id FROM publication_galleys g WHERE g.publication_id = s.current_publication_id ORDER BY g.seq, g.galley_id LIMIT 1),
    (SELECT g.submission_file_id FROM publication_galleys g WHERE g.publication_id = s.current_publication_id ORDER BY g.seq, g.galley_id LIMIT 1)
  FROM submissions s WHERE s.context_id = @ctx AND s.status = 3;

-- Where readers come from (relative weights)
CREATE TEMPORARY TABLE demo_countries (code CHAR(2), w INT);
INSERT INTO demo_countries VALUES
  ('US', 14), ('IN', 12), ('CN', 8), ('GB', 7), ('DE', 6), ('BR', 6), ('NG', 5), ('IR', 5),
  ('PK', 4), ('EG', 4), ('ET', 3), ('KE', 3), ('TR', 3), ('FR', 3), ('IT', 3), ('AU', 3),
  ('CA', 3), ('ID', 3), ('ES', 2), ('BD', 2), ('MX', 2), ('ZA', 2), ('NL', 2), ('JP', 2),
  ('SA', 2), ('MY', 2), ('PL', 1), ('GH', 1), ('VN', 1), ('AR', 1), ('NZ', 1), ('SE', 1);
SET @wsum = (SELECT SUM(w) FROM demo_countries);

-- Article page views and file downloads
INSERT INTO metrics_submission (load_id, context_id, submission_id, representation_id, submission_file_id, file_type, assoc_type, date, metric)
  SELECT 'demo-seed', @ctx, a.submission_id, NULL, NULL, NULL, 1048585, t.d,
    GREATEST(1, ROUND(a.w * t.g * t.span * (4 + (CRC32(CONCAT('v', a.submission_id, t.d)) % 50) / 10)))
  FROM demo_articles a JOIN demo_dates t;
INSERT INTO metrics_submission (load_id, context_id, submission_id, representation_id, submission_file_id, file_type, assoc_type, date, metric)
  SELECT 'demo-seed', @ctx, a.submission_id, a.galley_id, a.file_id, 2, 515, t.d,
    ROUND(a.w * t.g * t.span * (1.5 + (CRC32(CONCAT('d', a.submission_id, t.d)) % 30) / 10))
  FROM demo_articles a JOIN demo_dates t WHERE a.galley_id IS NOT NULL;

-- Journal home page and issue pages
INSERT INTO metrics_context (load_id, context_id, date, metric)
  SELECT 'demo-seed', @ctx, t.d, ROUND(t.g * t.span * (18 + CRC32(CONCAT('h', t.d)) % 22)) FROM demo_dates t;
INSERT INTO metrics_issue (load_id, context_id, issue_id, issue_galley_id, date, metric)
  SELECT 'demo-seed', @ctx, i.issue_id, NULL, t.d, ROUND(t.g * t.span * (3 + CRC32(CONCAT('i', i.issue_id, t.d)) % 8))
  FROM issues i JOIN demo_dates t WHERE i.journal_id = @ctx AND i.published = 1;

-- COUNTER figures (unique readers), daily for the last 30 days and monthly
INSERT INTO metrics_counter_submission_daily (load_id, context_id, submission_id, date, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
  SELECT 'demo-seed', context_id, submission_id, date, SUM(metric),
    ROUND(SUM(IF(assoc_type = 1048585, metric, 0)) * .82),
    SUM(IF(assoc_type = 515, metric, 0)), ROUND(SUM(IF(assoc_type = 515, metric, 0)) * .8)
  FROM metrics_submission WHERE context_id = @ctx AND date >= CURDATE() - INTERVAL 29 DAY
  GROUP BY context_id, submission_id, date;
INSERT INTO metrics_counter_submission_monthly (context_id, submission_id, month, metric_investigations, metric_investigations_unique, metric_requests, metric_requests_unique)
  SELECT context_id, submission_id, DATE_FORMAT(date, '%Y%m'), SUM(metric),
    ROUND(SUM(IF(assoc_type = 1048585, metric, 0)) * .82),
    SUM(IF(assoc_type = 515, metric, 0)), ROUND(SUM(IF(assoc_type = 515, metric, 0)) * .8)
  FROM metrics_submission WHERE context_id = @ctx
  GROUP BY context_id, submission_id, DATE_FORMAT(date, '%Y%m');

-- Readers by country, daily for the last 30 days and monthly
INSERT INTO metrics_submission_geo_daily (load_id, context_id, submission_id, country, region, city, date, metric, metric_unique)
  SELECT * FROM (
    SELECT 'demo-seed', c.context_id, c.submission_id, k.code, '' AS region, '' AS city, c.date,
      ROUND(c.metric_investigations * k.w / @wsum * (.5 + (CRC32(CONCAT(c.submission_id, k.code, c.date)) % 100) / 100)) AS metric,
      ROUND(c.metric_investigations_unique * k.w / @wsum * (.5 + (CRC32(CONCAT(c.submission_id, k.code, c.date)) % 100) / 100)) AS metric_unique
    FROM metrics_counter_submission_daily c JOIN demo_countries k WHERE c.context_id = @ctx
  ) x WHERE x.metric_unique > 0;
INSERT INTO metrics_submission_geo_monthly (context_id, submission_id, country, region, city, month, metric, metric_unique)
  SELECT * FROM (
    SELECT c.context_id, c.submission_id, k.code, '' AS region, '' AS city, c.month,
      ROUND(c.metric_investigations * k.w / @wsum * (.5 + (CRC32(CONCAT(c.submission_id, k.code, c.month)) % 100) / 100)) AS metric,
      ROUND(c.metric_investigations_unique * k.w / @wsum * (.5 + (CRC32(CONCAT(c.submission_id, k.code, c.month)) % 100) / 100)) AS metric_unique
    FROM metrics_counter_submission_monthly c JOIN demo_countries k WHERE c.context_id = @ctx
  ) x WHERE x.metric_unique > 0;

DROP TEMPORARY TABLE demo_dates, demo_articles, demo_countries;
