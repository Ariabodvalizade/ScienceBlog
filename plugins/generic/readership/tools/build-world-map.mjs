/**
 * plugins/generic/readership/tools/build-world-map.mjs
 *
 * Distributed under the GNU GPL v3.
 *
 * Builds assets/world.json, the pre-projected world map used by the
 * Readership page and the public "Readers around the world" section.
 * Run once when the map needs to change; the result is committed, so the
 * site itself needs no JavaScript or map library.
 *
 *   npm install --no-save world-atlas@2 topojson-client@3 d3-geo@3 i18n-iso-countries@7
 *   node plugins/generic/readership/tools/build-world-map.mjs
 *
 * Source: Natural Earth 1:110m via world-atlas (public domain / ISC).
 * Output: { width, height, countries: { IT: { d: "M…", c: [x, y] }, … } }
 * where d is the SVG path (Equal Earth projection) and c is a point inside
 * the country's largest landmass, used for the live visitor dots. Small
 * states missing from the 1:110m data (Singapore, Malta, …) get only c.
 */

import { createRequire } from 'node:module';
import { writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const topology = require('world-atlas/countries-110m.json');
const { feature } = require('topojson-client');
const { geoEqualEarth, geoPath, geoArea } = require('d3-geo');
const countries = require('i18n-iso-countries');

const WIDTH = 960;
// Natural Earth 110m has no ISO code for these
const EXTRA = { 'N. Cyprus': 'CY', Kosovo: 'XK', Somaliland: 'SO' };
const SKIP = new Set(['AQ']); // Antarctica: large and never has readers
// Small states and territories too small for the 1:110m map: a dot only [lon, lat]
const SMALL = {
  AD: [1.52, 42.51], AG: [-61.8, 17.07], AW: [-69.97, 12.52], BB: [-59.54, 13.19], BH: [50.56, 26.07],
  BM: [-64.75, 32.3], CV: [-23.6, 15.1], CW: [-68.99, 12.17], DM: [-61.37, 15.41], FO: [-6.9, 62.0],
  GD: [-61.68, 12.12], GI: [-5.35, 36.14], GP: [-61.55, 16.25], GU: [144.79, 13.44], HK: [114.17, 22.32],
  IM: [-4.55, 54.23], JE: [-2.13, 49.21], KM: [43.87, -11.88], KN: [-62.78, 17.36], KY: [-81.25, 19.31],
  LC: [-60.98, 13.91], LI: [9.55, 47.17], MC: [7.42, 43.74], MO: [113.54, 22.2], MQ: [-61.02, 14.64],
  MT: [14.44, 35.94], MU: [57.55, -20.2], MV: [73.51, 4.18], PF: [-149.4, -17.65], RE: [55.53, -21.11],
  SC: [55.45, -4.68], SG: [103.82, 1.35], SM: [12.46, 43.94], ST: [6.61, 0.19], TO: [-175.2, -21.18],
  VA: [12.45, 41.9], VC: [-61.2, 13.25], WS: [-172.1, -13.76],
};

const land = feature(topology, topology.objects.countries).features
  .map((f) => ({ ...f, code: f.id ? countries.numericToAlpha2(f.id) : EXTRA[f.properties.name] }))
  .filter((f) => f.code && !SKIP.has(f.code));

const projection = geoEqualEarth().fitWidth(WIDTH, { type: 'FeatureCollection', features: land });
const path = geoPath(projection).digits(0);
const [[, y0], [, y1]] = path.bounds({ type: 'FeatureCollection', features: land });
projection.translate([projection.translate()[0], projection.translate()[1] - y0 + 2]);
const height = Math.ceil(y1 - y0 + 4);

const out = {};
for (const f of land) {
  const d = path(f);
  if (!d) continue;
  // Dot position: centroid of the largest polygon (so France sits in Europe, not between it and Guiana)
  const polygons = f.geometry.type === 'MultiPolygon' ? f.geometry.coordinates : [f.geometry.coordinates];
  const largest = polygons
    .map((coordinates) => ({ type: 'Polygon', coordinates }))
    .sort((a, b) => geoArea(b) - geoArea(a))[0];
  const c = path.centroid(largest).map((n) => Math.round(n * 10) / 10);
  if (out[f.code]) {
    // Merge split entries (e.g. Cyprus + N. Cyprus) into one path, keep the larger one's dot
    out[f.code].d += d;
  } else {
    out[f.code] = { d, c };
  }
}

for (const [code, lonLat] of Object.entries(SMALL)) {
  if (!out[code]) {
    out[code] = { c: projection(lonLat).map((n) => Math.round(n * 10) / 10) };
  }
}

const file = join(dirname(fileURLToPath(import.meta.url)), '..', 'assets', 'world.json');
writeFileSync(file, JSON.stringify({ width: WIDTH, height, countries: out }));
console.log(`${Object.keys(out).length} countries, ${WIDTH}×${height} → ${file}`);
