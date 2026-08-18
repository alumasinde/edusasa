# Phase 11 — Timetable

Phase 11 turns the Phase 10 teaching workload into a real school timetable engine.

## Delivered

- Tenant-scoped timetable records per academic term
- Configurable teaching periods with safe default school periods
- Monday–Friday timetable grid
- Class and stream filtering
- Manual lesson placement
- Teacher and class/stream conflict detection
- Constraint-aware automatic generation from class-specific teacher assignments
- Periods-per-week workload support
- Consecutive double-period support
- Failed workload reporting rather than silent loss
- Draft / published / archived timetable lifecycle
- Timetable permissions and module entitlement middleware
- Migration and documentation aligned with the canonical migration registry

## Generation rules

1. Only active teachers and class-specific Phase 10 assignments are generated automatically.
2. Break periods are never used.
3. A teacher cannot teach two classes in the same day/period.
4. A class/stream cannot receive two lessons in the same day/period.
5. Requested periods per week are treated as workload, not a promise that an impossible timetable will fit.
6. Double periods are placed consecutively where two adjacent teaching periods are available.
7. Existing entries are cleared before a fresh automatic generation so the generated timetable is deterministic for the current workload and slot capacity.
8. Publishing is an explicit action; generation always returns the timetable to draft status.

## Recommended operational flow

Academic Year/Term → Classes/Streams → Subjects → Teachers → Phase 10 class-specific teaching assignments → Create Timetable → Generate → Review conflicts/free slots → Manually adjust if needed → Publish.

## Limitation intentionally left for the next iteration

The generator is a practical greedy constraint solver. It does not yet model room capacity, teacher unavailable periods, subject-specific day restrictions, assemblies, clubs, or cross-campus shared resources. Those constraints should be added as explicit data rather than hardcoded rules in the next timetable enhancement.
