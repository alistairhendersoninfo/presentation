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
    tag_info = []
    for entry in flow:
        tag = entry["tag"].strip()
        mandatory = bool(entry.get("mandatory", False))
        tag_info.append({
            "tag": tag,
            "mandatory": mandatory,
            "description": entry.get("description", "")
        })
        tags.append(tag)
    return tags, tag_info

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
            shapes = []
            # Collect details for this layout
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
                elif l.startswith("|") and section:
                    cols = [c.strip() for c in l.strip('| \n').split('|')]
                    if section == "placeholders" and len(cols) >= 6 and cols[0] != 'idx':
                        # idx, type, action, Title, description, text_allowed
                        placeholders.append({
                            "idx": cols[0],
                            "type": cols[1],
                            "action": cols[2],
                            "title": cols[3],
                            "description": cols[4],
                            "text_allowed": cols[5],
                        })
                    elif section == "shapes" and len(cols) >= 4 and cols[0] != 'Title':
                        shapes.append({
                            "title": cols[0],
                            "action": cols[1],
                            "description": cols[2],
                            "text_allowed": cols[3],
                        })
                idx += 1
            # Summary as human text
            summary = []
            for p in placeholders:
                summary.append(f"{p['title']} | {p['description']}")
            for s in shapes:
                summary.append(s["title"])
            layouts.append({
                "layout_index": layout_idx,
                "layout_name": layout_name,
                "placeholders": placeholders,
                "shapes": shapes,
                "summary": summary
            })
        else:
            idx += 1
    return layouts

def main():
    # Load tag info
    if not os.path.exists(FLOW_JSON):
        print(json.dumps({"error": f"Can't find '{FLOW_JSON}' in current directory."}))
        sys.exit(1)
    tags, tag_info = load_flow_json(FLOW_JSON)

    # Load layouts with metadata
    audit_md = find_first_audit_md()
    if audit_md is None:
        print(json.dumps({"error": "No _audit.md file found in current directory."}))
        sys.exit(1)
    layouts = parse_layouts_from_audit(audit_md)

    result = {
        "slide_tags": tag_info,    # Column 1: slide tags, descriptions, mandatory
        "layouts": layouts         # Column 2: layout index, name, placeholders/shapes
    }
    print(json.dumps(result, indent=2, ensure_ascii=False))

if __name__ == "__main__":
    main()
