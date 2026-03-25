# QvgaMaps - Simplified Test Plan

**Max 20 tests covering core user workflows only**

---

## Test Suite 1: Map Basics (4 tests)

### 1. Page loads with default map
- Load /
- Expect: Map image visible, Berlin coords (52.52, 13.4), zoom 14, Streets style
- Check: navigation buttons, search input, style links all present

### 2. Move right updates longitude
- Click map-right button
- Expect: longitude increases, page reloads, map changes

### 3. Zoom in/out works
- Click zoom-in: expect zoom increases
- Click zoom-out: expect zoom decreases
- At boundaries (1 or 22): zoom stays clamped

### 4. All 4 directions work
- Test: left, right, up, down each changes correct coordinate

---

## Test Suite 2: Search (7 tests)

### 5. Search "Berlin" returns results
- Enter "Berlin", click SEARCH
- Expect: Results page with up to 5 items, each has `data-testid="search-result-item-N"`

### 6. Empty search shows "No results"
- Submit empty form
- Expect: Message "No results found for """

### 7. Invalid location shows "No results"
- Search "xyz123nonexistent"
- Expect: "No results found for "xyz123nonexistent""

### 8. Special characters work
- Search "São Paulo"
- Expect: Proper UTF-8 display, no errors

### 9. Select result moves map
- Search "Paris", click first result
- Expect: Redirect to map page, coords ~48.85, 2.35 (Paris), map centered there

### 10. Back to map preserves state
- From results page, click "Back to Map"
- Expect: Returns to previous map location unchanged

### 11. Complex address search
- Search "Brandenburg Gate, Berlin"
- Expect: Results include Brandenburg Gate, can select

---

## Test Suite 3: Map Styles (3 tests)

### 12. Switch to Satellite
- Click "Satellite" link
- Expect: Satellite imagery loads, active indicator, coords unchanged

### 13. Switch to Outdoors
- Click "Outdoors"
- Expect: Terrain view loads, active indicator

### 14. Style persists after search
- Set Satellite, search and select location
- Expect: New location shown in Satellite style

---

## Test Suite 4: Complete Workflows (6 tests)

### 15. Basic search → navigate workflow
- Load (Berlin) → Search "Eiffel Tower" → select result
- Verify: coords = Paris (~48.86, 2.29)
- Zoom in 2x, pan left, verify map shows Eiffel Tower area

### 16. Multiple searches in session
- Search "Tokyo", select → verify Tokyo coords
- Search "New York", select → verify NY coords
- Verify final location is New York

### 17. Navigation after search selection
- Search "London", select result
- Click zoom-in 3 times → verify zoom increases
- Click right → verify lon increases
- All work correctly from London

### 18. Style switching + search
- Switch to Outdoors style
- Search "Grand Canyon", select
- Expect: Grand Canyon in Outdoors terrain view

### 19. Search + use back button
- Search location, get results
- Use browser back (not "Back to Map" link)
- Expect: Return to results page, can still select

### 20. Map image updates on navigation
- Load map, note appearance
- Click any navigation button
- Expect: Map image changes (different URL, different area)

---

## Implementation Notes

**Total**: 20 tests in 4 focused suites

**Selector Reference**:
- Map: `[data-testid="map"]`
- Search input: `[data-test-id="search-address"]`
- Search submit: `[data-testid="search-submit"]`
- Nav buttons: `[data-testid="map-{left|right|up|down}"]`, `[data-testid="map-zoom-{in|out}"]`
- Style links: `[data-testid="{streets-v12|outdoors-v12|satellite-v9}"]`
- Results: `[data-testid="search-result-item-{0-4}"]`
- Back links: `[data-testid="search-result-back-{header|footer}"]`

**Key Assertions**:
- Coordinates change: parse text from `p:has-text("Lat:")`, verify deltas
- Map reloads: navigation triggers page reload (form POST)
- Styles persist: check active indicator and page title
- Search limit: exactly 5 results max

**Skip for simplicity**:
- Caching behavior
- Geocoding details
- API failure handling
- Rate limiting
- Accessibility deep-dive
- Browser compatibility matrix
- Performance metrics
- Security penetration tests

**What this covers**:
- ✅ Primary user journey (search → select → explore)
- ✅ Map navigation works
- ✅ Style switching works
- ✅ Session state preserved across flows
- ✅ Basic error paths (empty/invalid search)

---

**Result**: 20 focused tests that verify the app works for real users. Easy to maintain, quick to run.