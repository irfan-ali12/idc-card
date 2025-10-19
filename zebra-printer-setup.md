# Zebra ZC300/CC300 Printer Setup — CR80 Portrait, Edge-to-Edge

## Goal: 100% coverage with crisp 300 DPI output

This plugin is tuned for Zebra ZC300/CC300-class card printers using the CR80 card size in portrait orientation (53.98mm × 85.6mm) at 300 DPI. Code applies a minimal background-only bleed to cover driver/PDF tolerances without shifting content.

### 1) Page size and margins (done in code)
The print window enforces the exact card size and removes margins:
```css
@page { size: 53.98mm 85.6mm; margin: 0; }
```
Additionally, print hints are set for high resolution:
```css
@media print { image-resolution: 300dpi; -webkit-image-resolution: 300dpi; }
```
And a minimal background-only bleed is used to ensure edge coverage:
```css
.page {
   background-size: calc(53.98mm + 1mm) calc(85.6mm + 1mm);
   background-position: -0.5mm -0.5mm;
}
```

### 2) Print dialog settings (Chrome or Edge)
- Scale: 100% (do NOT use “Fit to page”)
- Margins: None
- Headers/Footers: Off
- Background graphics: On
- Destination: your Zebra driver (or “Save as PDF” to verify first)

### 3) Zebra driver preferences (ZC300/CC300)
- Card size/type: CR80 (approx. 54mm × 86mm)
- Orientation: Portrait
- Print quality: 300 DPI / High Quality
- Duplex: Enabled
   - Flip/binding: Long edge (for portrait) to keep both sides upright
- Edge-to-edge / Image to edge: Enabled
- Image position/offset X/Y: 0.0 mm
- Ribbon: YMCKO (for full-color) or as appropriate
- Card thickness: 30 mil (standard)
- Print area: Full card

### 4) Minimal background-only bleed (done in code)
- Background extends by 0.5mm on each side (1mm total in both dimensions)
- Content stays fixed (no layout shift), maintaining precise positions
- Covers typical PDF/printer edge tolerances while preserving design

### Troubleshooting

### PDF Verification Steps

#### Before Printing - Verify 100% Coverage:
1. **Save as PDF first**: Use "Print to PDF" to verify coverage
2. **Check PDF edges**: Open PDF and zoom to 200-400%
3. **Verify no white gaps**: Background should extend to all edges
4. **Content positioning**: Text/images should be properly centered in safe area

#### Expected PDF appearance:
- Background completely fills with no white borders
- Dimensions: 53.98mm × 85.6mm (portrait) with full coverage
- Content: Positions match preview; text and rings unchanged
- Quality: Sharp at 300 DPI

### Troubleshooting

#### If PDF still shows gaps:
1. **Clear browser cache** completely and reload
2. **Check CSS loading**: Ensure all stylesheets are loaded
3. **Browser zoom**: Set to exactly 100% before printing
4. **Print dialog**: Use system dialog or ensure the browser dialog honors 100% scale + no margins

#### If Zebra CC300 still shows gaps after PDF is perfect:
1. **CC300 Driver Updates**:
   - Download latest CC300 driver from Zebra website
   - Ensure driver version supports edge-to-edge printing

2. **CC300/ZC300 Hardware Settings**:
   - Card thickness sensor: Ensure proper detection
   - Card alignment: Check mechanical guides
   - Print head pressure: May need adjustment for edge coverage

3. **CC300/ZC300 Advanced Settings**:
   - Print density: Increase slightly (but not too much)
   - Print speed: Use medium speed for better edge quality
   - Ribbon tension: Ensure proper ribbon installation
   - Duplex alignment: If front/back are misaligned, try adjusting the driver’s side B offset or flip mode

---

Note: The print templates suppress a trailing blank page to help the driver keep duplex in sync. If your driver still inserts blanks, re-check the Duplex settings and ensure “pages per sheet” is 1.

#### For optimal quality:
- Use high-quality card stock (CR80 standard)
- Ensure card stock is clean and properly aligned
- Check printer head cleanliness
- Use recommended ribbon/ink cartridges

### Technical Details

The print optimization includes:
- Absolute positioning to eliminate browser margins
- Exact canvas dimensions matching CR80 standard
- Hardware acceleration for smooth rendering
- High DPI settings for crisp output
- Edge-to-edge background sizing
- Removed border radius for seamless printing

### Support

If you continue to experience printing issues:
1. Check your Zebra printer manual for CR80 card printing instructions
2. Verify your printer supports the exact dimensions
3. Test with different card stock
4. Contact your printer manufacturer for driver updates