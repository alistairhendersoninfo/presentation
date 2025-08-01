import json
import re
import sys
import os

FLOW_JSON = "presentation_flow.json"

def find_first_audit_md():
    for f in os.listdir('.'):
        if f.lower().endswith('_audit.md'):
            return f
    return None

def load_flow_json(json_path):
    with open(json_path, "r", encoding="utf-8") as f:
        flow = json.load(f)
    tags = []
    mandatory_status = {}
    descriptions = {}
    for entry in flow:
        tag = entry["tag"].strip()
        tags.append(tag)
        mandatory_status[tag] = "MANDATORY" if entry["mandatory"] else "OPTIONAL"
        descriptions[tag] = entry["description"]
    return tags, mandatory_status, descriptions

def summarise_layout(details):
    ph_types = []
    has_bg = False
    has_table = False
    has_image = False
    editable_titles = []
    for line in details:
        l = line.strip().lower()
        if l.startswith('| idx') or l.startswith('|---'):
            continue
        # Placeholders
        m = re.match(r"\| *(\d+) *\| *([\w\(\) ]+) *\| *(true|false)", l)
        if m:
            idx, type_text, editable = m.group(1), m.group(2), m.group(3)
            if "title" in type_text.lower():
                ph_types.append("Title" if editable == "true" else "Title (fixed)")
                if editable == "true":
                    editable_titles.append("Title")
            elif "subtitle" in type_text.lower():
                ph_types.append("Subtitle" if editable == "true" else "Subtitle (fixed)")
            elif "body" in type_text.lower():
                ph_types.append("Body")
            elif "table" in type_text.lower():
                ph_types.append("Table")
                has_table = True
            elif "object" in type_text.lower() or "picture" in type_text.lower():
                ph_types.append("Image")
                has_image = True
        # Non-placeholder shapes for backgrounds
        if "background" in l:
            has_bg = True
    summary = []
    if ph_types:
        summary.append(" + ".join(sorted(set(ph_types))))
    if has_table:
        summary.append("Table")
    if has_image:
        summary.append("Image")
    if has_bg:
        summary.append("Background")
    if editable_titles:
        summary.append("Editable title")
    return "; ".join(summary) if summary else "Unknown layout"

def parse_layouts_from_audit(audit_md):
    layouts = {}
    with open(audit_md, "r", encoding="utf-8") as f:
        lines = f.readlines()
    idx = 0
    while idx < len(lines):
        line = lines[idx]
        match = re.match(r"### Layout (\d+):\s*(.*)", line.strip())
        if match:
            layout_idx = int(match.group(1))
            layout_name = match.group(2).strip()
            details = []
            idx += 1
            while idx < len(lines):
                if lines[idx].strip().startswith("### Layout ") or lines[idx].strip() == "---":
                    break
                details.append(lines[idx].rstrip('\n'))
                idx += 1
            summary = summarise_layout(details)
            layouts[layout_idx] = {
                "name": layout_name,
                "details": details,
                "summary": summary
            }
        else:
            idx += 1
    return layouts

def main():
    if not os.path.exists(FLOW_JSON):
        print(f"❌ Can't find '{FLOW_JSON}' in current directory.")
        sys.exit(1)
    tags, mandatory_status, descriptions = load_flow_json(FLOW_JSON)
    if not tags:
        print("❌ No slide tags found in flow JSON file. Is the format correct?")
        sys.exit(1)
    print("== SLIDE TYPES/TAGS FROM JSON FLOW FILE ==")
    for t in tags:
        print(f"  - {t} ({mandatory_status[t]}): {descriptions[t]}")

    audit_md = find_first_audit_md()
    if audit_md is None:
        print("❌ No _audit.md file found in current directory.")
        sys.exit(1)

    layouts = parse_layouts_from_audit(audit_md)
    print(f"\n== LAYOUTS IN '{audit_md}' ==\n")
    for idx in sorted(layouts.keys()):
        print(f"  Layout {idx}: '{layouts[idx]['name']}' [{layouts[idx]['summary']}]")

    # Interactive mapping: for each tag, show layouts + summaries before prompting
    tag_map = {}
    for tag in tags:
        print(f"\n==============================================")
        print(f"Tag: '{tag}' ({mandatory_status[tag]})")
        print(f"Description: {descriptions[tag]}")
        print(f"---------- Layouts (summary) --------------")
        for idx in sorted(layouts.keys()):
            print(f"  Layout {idx}: '{layouts[idx]['name']}' [{layouts[idx]['summary']}]")
        print("\n----------------------------------------------")
        while True:
            layout_input = input(f"  Enter layout index(es) (comma-separated) for tag '{tag}' (or leave blank to skip): ").strip()
            if layout_input == "":
                break
            indices = [x.strip() for x in layout_input.split(",") if x.strip().isdigit() and int(x.strip()) in layouts]
            if not indices:
                print("  Invalid input. Enter one or more valid layout indices separated by commas, or leave blank to skip.")
                continue
            tag_map[tag] = [int(x) for x in indices]
            break

    # Output summary table
    print("\n==== TAG/LAYOUT MATCH SUMMARY (Markdown Table) ====\n")
    print("| Tag/Slide Type           | Mandatory | Layout Indices | Layout Names / Summary         |")
    print("|--------------------------|-----------|---------------|-------------------------------|")
    for tag in tags:
        indices = tag_map.get(tag, [])
        if indices:
            names = "; ".join(
                f"{i}: {layouts[i]['name']} [{layouts[i]['summary']}]" for i in indices
            )
            indices_str = ",".join(str(i) for i in indices)
        else:
            names = "-"
            indices_str = "-"
        print(f"| {tag:<25} | {mandatory_status[tag]:<9} | {indices_str:<13} | {names:<29} |")

if __name__ == "__main__":
    main()
 