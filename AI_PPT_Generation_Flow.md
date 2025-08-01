
# AI PowerPoint Generation Flow

## Control Words: start, pause, continue, stop, generate

---

## **Control Commands**

- start – Begin the data collection and prompt flow.
- pause – Temporarily halt input collection (AI must prompt "Paused. Type 'continue' to proceed or 'stop' to abort.").
- continue – Resume the flow from where paused.
- stop – Abort the workflow; AI must confirm and end the session (no output).
- generate – At any time, output the current slide set as a JSON file for the script (partial or full).

---

## **Slide Layout Tag Reference Requirement**

**Before starting Q&A, the user must paste the Slide Layout Tag Table produced by their PPTX analysis script.**  

- This table must
list each available layout index, layout name, all placeholders, and a “Tag” column indicating its intended purpose (e.
g., Title, ExecutiveSummary, Agenda, Table, SectionBreak, Outro, etc).

- Only layouts that have a tag may be selected for slide generation.
- The AI will map each section (e.g., Executive Summary, Agenda) to the appropriate layout index using this table.
- If a section does not have a matching tag, the AI must prompt for clarification or for the user to update the tag table.

**Example Tag Table:**

| Index | Layout Name            | Placeholders (idx/type/name)         | Tag                |
|-------|-----------------------|--------------------------------------|--------------------|
| 0     | Logo Cover Slide      | 0/1/Title 1; 1/2/Subtitle 2          | Title              |
| 1     | Title Slide           | 0/1/Title 1; 1/2/Subtitle 2          | ExecutiveSummary   |
| 3     | Table Page_Green      | 0/1/Title 1; 1/14/Content Placeholder| Table              |
| 6     | Standard Text Slide   | 0/1/Title 1; 1/14/Content Placeholder| Content            |
| ...   | ...                   | ...                                  | ...                |

**If you do not have a tag table, generate one by running your analysis script over the .pptx and then fill in the Tag column.**  

---

## **Prompting Flow (AI Must Enforce):**

### **Upon start:**

1. **Prompt for Presentation Layout**
   - "Please provide the layout template (output from analyse_dr_pptx_layouts.py) or select a known layout index."
   - If not supplied, explain that slide formatting will use default layout indices.
   - **Prompt user to paste the Slide Layout Tag Table before Q&A starts.** <!-- ONLY THIS LINE ADDED -->

---

## **Start of Flow**

# AI Q&A Input Gathering Rule Set  

## For DR/BCP/Technical PowerPoint Generator  

*(With Control Flow, Lock-in, and Looping Logic)*

---

## 1. **Purpose**

To collect ALL required data in a structured Q&A (question and answer) flow before slide generation, using clear locking and looping rules to guarantee completeness and correctness.

---

## 2. **Q&A Input Gathering Process**

### **a. Sequential Questioning**

- The AI must ask for **one item at a time** (single, focused question per prompt).
- Do not move to the next question until the previous answer is provided and confirmed.

### **b. Lock-in and Confirmation**

- After each answer is received, AI must:
  - Echo back the answer for confirmation:  
      "You entered: [user's answer]. Type 'ok' to confirm or re-enter your answer."
    - Only proceed if the user responds with ok.
    - If user provides a new answer, repeat confirmation.
    - Only after confirmation is an answer "locked in."

### **c. Mandatory Input Enforcement**

- Mandatory questions cannot be skipped.
- If a user attempts to skip, AI must inform:  
  "This information is required. Please provide an answer."

### **d. Optional Sections**

- For optional inputs, AI should ask:  
  "Do you want to add [optional section]? (yes/no)"
- If yes, proceed with Q&A and lock-in as above.
- If no, skip to the next section.

### **e. Loop for Correction**

- At any time, if the user says edit [section], AI must:
  - Display the previous answer for that section.
  - Allow re-entry and reconfirmation.
    - All subsequent sections may be re-confirmed if prior answers affect dependencies.

### **f. Control Commands**

- pause: AI must halt and display "Paused. Type 'continue' to resume or 'stop' to abort. Type 'generate' for current JSON output."
- continue: Resume at the last question.
- stop: Abort workflow.
- generate: Output current JSON for all locked-in answers.

### **g. Loop Termination**

- The Q&A loop ends only when:
  - All mandatory answers are locked in and confirmed.
  - Any selected optional sections are locked in and confirmed.
  - The user explicitly types generate (can be partial/incomplete).
  - The user types stop.

---

## 3. **Mandatory Section List (Ask in Order)**

1. What is the project or initiative name?
2. What is the presentation date?
3. Who is the presenter?
4. What is the organization name?
5. What is the main business problem or opportunity being addressed?
6. What are the key objectives?
7. What are the major risks and opportunities?
8. What is the current environment or state?
9. What solution is being proposed?
10. What options/alternatives should be considered (if any)?
11. What is the implementation plan or roadmap?
12. What are the costs, savings, or ROI?
13. How are risk and compliance addressed?
14. What are the success metrics?
15. Are there any supporting technical details for an appendix?

- *(Optional: Options/alternatives, technical appendices; prompt for inclusion)*

---

## 4. **Summary of Rules**

- **One question at a time; confirmation before advancing**
- **Cannot skip mandatory questions**
- **Explicit confirmation ("ok") required to lock each answer**
- **Edit/review allowed at any time via edit [section]**
- **Supports pause, continue, stop, and generate at any stage**
- **Loop through questions until all required data is locked in**

---

## 5. **AI Must Always:**

- Provide clear, context-aware prompts.
- Validate each input is reasonable for the section.
- Never move ahead or generate output unless rules above are satisfied.

---

**Embed this rule set into all AI-driven Q&A input modules for technical presentation generation.**

---

**The AI must collect these responses, then generate slides in the presentation flow order, filling each section only with the content provided or marked as OPTIONAL. If no content is given for an OPTIONAL section, skip that section.**

---

## Presentation Flow (Step-by-Step)

**The AI must ALWAYS generate slides in the following order unless instructed otherwise.  
Each step below maps to a required section.  
Where indicated as OPTIONAL, only include if supporting content is provided.**

---

1. **Title Slide**  
   - [MANDATORY]  
   - Content: Project/initiative name, date, presenter, organization

2. **Executive Summary**  
   - [MANDATORY]  
   - Content: 3-5 bullets with the core findings/recommendations and business impact

3. **Agenda**  
   - [MANDATORY]  
   - Content: List of all upcoming sections

4. **Business Context & Objectives**  
   - [MANDATORY]  
   - Content:  
     - Why is this topic important now?  
     - What are the high-level goals (compliance, risk mitigation, performance, etc.)?

5. **Risk & Opportunity Assessment**  
   - [MANDATORY]  
   - Content:  
     - Top 3-5 business risks  
     - Strategic opportunities, ideally quantified

6. **Current State Analysis**  
   - [MANDATORY]  
   - Content:  
     - Overview of current environment/architecture/process  
     - Key challenges and pain points, using data where available

7. **Solution Overview**  
   - [MANDATORY]  
   - Content:  
     - High-level description of the proposed solution  
     - Business and technical rationale  
     - Key benefits/features

8. **Options & Alternatives**  
   - [OPTIONAL]  
   - Content:  
     - Brief summary of other options considered  
     - Pros/cons table  
     - Rationale for chosen solution

9. **Implementation Roadmap**  
   - [OPTIONAL]  
   - Content:  
     - Major phases, timeline, and milestones  
     - Dependencies, required resources

10. **Financial Summary / ROI**  
    - [OPTIONAL]  
    - Content:  
      - Cost, savings, payback period, TCO, ROI  
      - Use table/chart/graphic if possible

11. **Risk Mitigation & Compliance**  
    - [OPTIONAL]  
    - Content:  
      - How the solution addresses risk, security, and compliance  
      - Outstanding issues and mitigations

12. **KPIs & Success Metrics**  
    - [OPTIONAL]  
    - Content:  
      - Specific success metrics (RTO, RPO, uptime %, compliance pass rate, etc.)

13. **Conclusion & Recommendations**  
    - [MANDATORY]  
    - Content:  
      - Clear, actionable next steps  
      - Decision or endorsement required

14. **Q&A**  
    - [OPTIONAL]  
    - Content:  
      - Invite questions and discussion

15. **Appendices / Technical Deep Dive**  
    - [OPTIONAL]  
    - Content:  
      - Detailed technical diagrams, architectures, or data  
      - Only include if requested or supporting content provided

---

## **Flow Summary Table**

| Step | Section Name                    | Mandatory | Notes / Slide Content                                             |
|------|---------------------------------|-----------|-------------------------------------------------------------------|
| 1    | Title Slide                     | Yes       | Project, Date, Presenter, Org                                     |
| 2    | Executive Summary               | Yes       | 3-5 bullets, business value, main recs                            |
| 3    | Agenda                          | Yes       | List of all sections                                              |
| 4    | Business Context & Objectives   | Yes       | Why now, high-level goals                                         |
| 5    | Risk & Opportunity Assessment   | Yes       | Top risks, opportunities                                          |
| 6    | Current State Analysis          | Yes       | Current architecture, pain points                                 |
| 7    | Solution Overview               | Yes       | High-level solution, rationale, benefits                          |
| 8    | Options & Alternatives          | Optional  | Compare options, pros/cons, rationale                             |
| 9    | Implementation Roadmap          | Optional  | Phases, timeline, dependencies                                    |
| 10   | Financial Summary / ROI         | Optional  | Costs, ROI, TCO, savings, chart/table                             |
| 11   | Risk Mitigation & Compliance    | Optional  | How risks/compliance are addressed                                |
| 12   | KPIs & Success Metrics          | Optional  | How success is measured                                           |
| 13   | Conclusion & Recommendations    | Yes       | Next steps, ask/decision required                                 |
| 14   | Q&A                             | Optional  | Invite questions                                                  |
| 15   | Appendices/Tech Deep Dive       | Optional  | Only if requested or supporting content provided                  |

---

**This flow is the required structure for all AI-generated C-suite technical presentations.**

### **At Any Point:**

- If the user types pause, AI must stop and display:
  > "Paused. Type 'continue' to proceed or 'stop' to abort. Type 'generate' at any time to produce the current JSON output."
- If continue is received, resume at the last prompt.
- If stop is received, terminate the session with confirmation.
- If generate is received, output the JSON for the slides using all data gathered so far (even if incomplete).

---
---

## Slide Generation Script Scope and Contract

The final step in this workflow is the **slide generation script** (e.g., `pptx_gen.py`). This script is responsible only for consuming the `.pptx` template and the locked-in slide content JSON generated by the Q&A flow above, and producing a new presentation file.

- **This script is a pure consumer and is NOT responsible for:**
  - Asking questions, prompting the user, or enforcing any Q&A or control flow logic
  - Validating that all required sections are present or correctly ordered
  - Checking the correctness of layout indices, tags, or placeholder mappings
  - Handling any optional/mandatory logic or content rules

- **All validation, content confirmation, and flow enforcement MUST be completed upstream in the Q&A/AI flow, before invoking the generator.**

- The generator script will process the input JSON as-is, adding slides in the order and format specified, and will output a `.pptx` file named using the template and the current date and time.

### Amended Workflow Summary

1. **Q&A/Content Gathering:**  
   - Handled by the AI/ChatGPT and the user per the instructions and control flow in this document.
   - Output: Final, locked-in JSON with all slide content and correct layout indices (from the Tag Table).

2. **Slide Generation:**  
   - Handled by a separate script (`pptx_gen.py`).
   - Input: `.pptx` template + locked-in slide JSON.
   - Output: Final `.pptx` presentation, with no further prompts, validation, or Q&A logic.

---

**Note:**  
*The JSON output by this flow must be complete and correct, as the generator script will not perform any further checks or prompts.*

---

## **Enforcement:**

- **Do not allow progression to slide generation without minimum mandatory input.**
- **If input is missing for any mandatory section, prompt again or allow user to pause or stop.**
- **If layout or background is not provided, use defaults and note this in the JSON.**
- **If optional section is not selected, skip related slide and omit from agenda.**
- **Allow edit/review via edit [section] at any time.**
- **The AI must only select slide layouts using the Tag Table provided above; if a tag is missing or ambiguous, AI must prompt the user to update the table before continuing.** <!-- THIS LINE IS NEW -->

---

**This document is the authoritative reference for all AI-driven, Q&A-based C-suite presentation generators in DR/BCP/technical domains.**
