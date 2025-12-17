# Agent Usage Guide

How AI agents should use these optimized reference files.

## Purpose

These files are broken down from large project documents (100+ KB) into focused, single-topic files (2-8 KB each) for faster agent consumption and more efficient context usage.

## File Organization Principle

Each file covers **ONE specific topic** or **ONE bounded context**:
- Domain contexts are separated
- Business rules are isolated
- Implementation guides are distinct
- Reference data is categorized

## How to Use These Files

### For General Questions

**Q: "What's the current status?"**
→ Read: `/implementation/current-status.md`

**Q: "What should I do next?"**
→ Read: `/implementation/current-status.md` → Next Immediate Steps section

**Q: "What are the key business rules?"**
→ Read: `/business-rules/core-rules.md`

### For Domain Understanding

**Q: "What is a Workout in this system?"**
→ Read: `/domain/ubiquitous-language.md` → Search for "Workout"

**Q: "How do gyms work?"**
→ Read: `/domain/gym-context.md`

**Q: "What's the relationship between User and Profiles?"**
→ Read: `/domain/identity-context.md`

### For Implementation Tasks

**Task: "Create Equipment Seeder"**
→ Read:
1. `/reference/equipment-catalog.md` (data to seed)
2. `/implementation/current-status.md` (step details)

**Task: "Implement workout-gym compatibility"**
→ Read:
1. `/implementation/compatibility-algorithm.md` (algorithm)
2. `/domain/gym-context.md` (context)
3. `/business-rules/core-rules.md` (Rule 16)

**Task: "Build API endpoint for workouts"**
→ Read:
1. `/api/api-overview.md` (API structure)
2. `/domain/training-content-context.md` (domain model)
3. `/reference/architecture-patterns.md` (vertical slice pattern)

### For Database Work

**Task: "Understand migrations"**
→ Read: `/database/migrations-overview.md`

**Task: "Create model relationships"**
→ Read:
1. Relevant domain context file (e.g., `/domain/training-content-context.md`)
2. `/reference/quick-reference.md` → Relationships Cheat Sheet

### For Data Reference

**Q: "What equipment items exist?"**
→ Read: `/reference/equipment-catalog.md`

**Q: "What muscle groups are valid?"**
→ Read: `/reference/muscle-groups.md`

**Q: "What enums are used?"**
→ Read: `/reference/quick-reference.md` → Enum Values section

## Best Practices

### ✅ DO

1. **Read specific files for specific questions**
   - Don't load entire project docs
   - Use focused reference files

2. **Follow cross-references**
   - Files reference each other
   - Follow links for deeper understanding

3. **Check INDEX.md first**
   - Quick navigation
   - Find relevant files fast

4. **Use Quick Reference for common lookups**
   - Commands, enums, relationships
   - Fast answers to simple questions

5. **Read Current Status before implementing**
   - Know what's done
   - Know what's next
   - Avoid duplicate work

### ❌ DON'T

1. **Don't load all files at once**
   - Wastes context tokens
   - Most info not relevant to current task

2. **Don't skip Business Rules**
   - Critical rules must be enforced
   - Always check relevant rules before implementing

3. **Don't ignore domain context**
   - Understand domain model first
   - Then implement features

4. **Don't assume frontend work**
   - This is backend API only
   - No views, templates, or client code

## File Loading Strategy

### For Quick Questions (1-2 files)
Load only what you need:
- Quick lookup → `/reference/quick-reference.md`
- Current status → `/implementation/current-status.md`
- Specific domain → relevant context file

### For Feature Implementation (3-5 files)
Load in order:
1. Domain context file (understand model)
2. Business rules file (understand constraints)
3. API overview (understand interface)
4. Architecture patterns (understand structure)
5. Current status (know progress)

### For Complex Features (5-7 files)
Load progressively:
1. Start with domain and rules
2. Add implementation guides
3. Add reference data as needed
4. Don't load files you don't need yet

## Example Workflows

### Workflow 1: Create Equipment Seeder

**Files to read** (in order):
1. `/implementation/current-status.md` → Step 2 section
2. `/reference/equipment-catalog.md` → Full data list
3. `/reference/quick-reference.md` → Seeder command

**Action**: Create seeder with ~60 items, run command.

### Workflow 2: Implement Workout-Gym Compatibility

**Files to read** (in order):
1. `/implementation/compatibility-algorithm.md` → Algorithm details
2. `/domain/gym-context.md` → Critical Feature section
3. `/domain/training-content-context.md` → Workout structure
4. `/business-rules/core-rules.md` → Rule 16
5. `/reference/architecture-patterns.md` → Action class pattern

**Action**: Create CheckWorkoutCompatibilityAction, write tests.

### Workflow 3: Build Workout Create Endpoint

**Files to read** (in order):
1. `/domain/training-content-context.md` → Workout entity, rules
2. `/api/api-overview.md` → API structure, response format
3. `/business-rules/core-rules.md` → Rules 5, 6
4. `/reference/architecture-patterns.md` → Vertical slice, TDD
5. `/reference/quick-reference.md` → Route location

**Action**: Create Request, Action, Resource, Test, Route.

## Token Efficiency

### Original Docs
- `FitTrack_Project_Document.md`: ~70KB, ~18K tokens
- `IMPLEMENTATION_PROGRESS.md`: ~20KB, ~5K tokens
- `START_HERE.md`: ~10KB, ~2.5K tokens
- **Total**: ~100KB, ~25K tokens

### Optimized Agent Files
- 14 focused files
- **Total**: ~40KB, ~10K tokens
- **Average per file**: ~3KB, ~750 tokens

### Savings
- **60% less data** to load
- **60% fewer tokens** per query
- **Faster context building**
- **More focused information**

## Navigation Tips

### Use INDEX.md
Central navigation hub with:
- File descriptions
- Quick lookups section
- Common tasks mapping
- Learning paths

### Use README.md
Directory overview:
- Structure explanation
- Directory purposes
- Usage notes

### Use Cross-References
Files reference each other:
- "See `/domain/gym-context.md` for..."
- "Full details in `/implementation/compatibility-algorithm.md`"
- Follow links for deeper understanding

## Updating Files

When project changes:
1. Update relevant specific file (not all files)
2. Update INDEX.md if new files added
3. Update AGENT_USAGE_GUIDE.md if usage patterns change
4. Keep files focused and single-topic

## File Maintenance

### Keep Files Small
- Target: 2-8 KB per file
- If file grows > 10 KB, consider splitting
- One topic = one file

### Keep Files Current
- Update when business rules change
- Update when architecture changes
- Update when progress changes

### Keep Files Focused
- No duplicate information across files
- Cross-reference instead of repeat
- Single source of truth per topic

---

**Agent Note**: These files are your optimized reference library. Use them efficiently to minimize context usage and maximize productivity.
