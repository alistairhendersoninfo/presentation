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
    tag_info = []
    for entry in flow:
        tag_info.append({
            "tag": entry.get("tag", "").strip(),
            "mandatory": bool(entry.get("mandatory", False)),
            "description": entry.get("description", "")
        })
    return tag_info

def parse_layouts_from_audit(audit_md):
    layouts = []
    with open(audit_md, "r", encoding="utf-8") as f:
        lines = f.readlines()
    idx = 0
    while idx < len(lines):
        line = lines[idx]
        match = re.match(r"### Layout (\d+):\s*(.*)", line.strip())
        if match:
            layout_idx = int(match.group(1))
            layout_name = match.group(2).strip()
            placeholders = []
            shape_descriptions = []
            idx += 1
            section = None
            while idx < len(lines):
                l = lines[idx].strip()
                if l.startswith("#### Placeholders"):
                    section = "placeholders"
                elif l.startswith("#### Non-placeholder shapes"):
                    section = "shapes"
                elif l.startswith("### Layout ") or l == "---":
                    break
                elif l.startswith("|") and section == "placeholders":
                    cols = [c.strip() for c in l.strip('| \n').split('|')]
                    # Exclude header and all-dash rows
                    if cols and cols[0] not in ('idx', '---', 'Title'):
                        # Try to get title and description from standard audit output
                        if len(cols) >= 5:
                            placeholders.append({
                                "title": cols[3],
                                "description": cols[4]
                            })
                        elif len(cols) >= 2:
                            placeholders.append({
                                "title": cols[0],
                                "description": cols[1] if len(cols) > 1 else ""
                            })
                elif l.startswith("|") and section == "shapes":
                    cols = [c.strip() for c in l.strip('| \n').split('|')]
                    # Only process if at least 3 columns and not header/dash, and description exists
                    if len(cols) >= 3 and cols[0] != 'Title' and cols[0] != '---' and cols[2]:
                        shape_descriptions.append(cols[2])
                idx += 1
            layouts.append({
                "layout_index": layout_idx,
                "layout_name": layout_name,
                "placeholders": placeholders,
                "shape_descriptions": shape_descriptions
            })
        else:
            idx += 1
    return layouts

def main():
    if not os.path.exists(FLOW_JSON):
        print(json.dumps({"error": f"Can't find '{FLOW_JSON}' in current directory."}))
        sys.exit(1)
    tag_info = load_flow_json(FLOW_JSON)
    audit_md = find_first_audit_md()
    if audit_md is None:
        print(json.dumps({"error": "No _audit.md file found in current directory."}))
        sys.exit(1)
    layouts = parse_layouts_from_audit(audit_md)
    result = {
        "slide_tags": tag_info,
        "layouts": layouts
    }
    print(json.dumps(result, indent=2, ensure_ascii=False))

if __name__ == "__main__":
    main()
