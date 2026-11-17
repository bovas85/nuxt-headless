module.exports = async function triageIssue({
  github,
  context,
  core,
  verifyCommand = "npm run verify",
  owner = context.repo.owner,
} = {}) {
  const issue = context.payload.issue;
  const title = issue.title || "";
  const body = issue.body || "";
  const existingLabels = (issue.labels || []).map((l) =>
    (l.name || "").toLowerCase(),
  );
  const fullText = `${title}\n${body}`.toLowerCase();

  const isCleanup =
    existingLabels.includes("cleanup") ||
    title.toLowerCase().startsWith("chore(cleanup)") ||
    fullText.includes("## standardized cleanup specification") ||
    (fullText.includes("ready when") && fullText.includes("how to verify"));
  const isCleanupReady = existingLabels.includes("cleanup-ready");
  const isFriction =
    existingLabels.includes("friction") ||
    title.toLowerCase().startsWith("friction:") ||
    fullText.includes("## standardized friction log") ||
    (fullText.includes("what happened") &&
      fullText.includes("how to reproduce"));

  const oneWayIndicators = [
    "drop table",
    "drop column",
    "delete database",
    "database migration",
    "schema migration",
    "pricing change",
    "billing tier",
    "payment processing",
    "auth breaking change",
    "api breaking change",
    "deprecate endpoint",
    "remove domain",
    "dns change",
    "infrastructure teardown",
    "irreversible",
  ];
  const matchedOneWay = oneWayIndicators.filter((kw) => fullText.includes(kw));
  const isOneWayDoor = matchedOneWay.length > 0;
  const doorLabel = isOneWayDoor ? "one-way-door" : "two-way-door";
  const doorTypeDesc = isOneWayDoor
    ? "Single Entry / Major Irreversible Change"
    : "Double Entry / Easily Reversible";

  const hardIndicators = [
    "architecture",
    "major refactor",
    "refactor entire",
    "redesign auth",
    "multi-tenant",
    "distributed",
    "concurrency",
    "deadlock",
    "high load",
    "complex state",
    "breaking change",
    "cross-cutting",
    "deep overhaul",
    "security audit required",
    "rfc",
    "proposal",
    "needs human",
    "decision needed",
    "pricing",
    "billing",
    "legal",
  ];
  const matchedHard = hardIndicators.filter((kw) => fullText.includes(kw));

  const easyIndicators = [
    "style",
    "css",
    "color",
    "typography",
    "font",
    "spacing",
    "padding",
    "margin",
    "button",
    "text",
    "typo",
    "copy",
    "icon",
    "tooltip",
    "alt text",
    "aria",
    "cleanup",
    "lint",
    "prettier",
    "format",
  ];
  const matchedEasy = easyIndicators.filter((kw) => fullText.includes(kw));
  const isEasyPrefix =
    /^(fix\((style|css|copy|typo)\)|chore\((deps|lint|format|cleanup)\)|docs:)/i.test(
      title,
    );

  let complexityLevel;
  let complexityRationale;
  if (matchedHard.length > 0 || isOneWayDoor) {
    complexityLevel = "hard";
    complexityRationale = isOneWayDoor
      ? `High complexity driven by one-way door characteristics (${matchedOneWay.join(", ")}).`
      : `High complexity / cross-cutting indicators detected (${matchedHard.join(", ")}).`;
  } else if (matchedEasy.length > 0 || isEasyPrefix) {
    complexityLevel = "easy";
    complexityRationale =
      "Low cognitive load: isolated UI, styling, copy, cleanup, or self-contained defect fix.";
  } else {
    complexityLevel = "moderate";
    complexityRationale =
      "Moderate complexity: contained multi-file component, CLI, or endpoint integration.";
  }
  const complexityLabel = `complexity:${complexityLevel}`;

  const existingPriority = ["p0", "p1", "p2", "p3"].find((p) =>
    existingLabels.includes(p),
  );
  const bodyPriorityMatch = body.match(
    /(?:^|\n)### Priority\s*\n+\s*(P[0-3])\b/i,
  );
  const explicitPriorityMatch =
    bodyPriorityMatch ||
    `${title}\n${body}`.match(/\b(?:priority[:\s]+)?(P[0-3])\b/i);

  let priorityLabel;
  if (existingPriority) {
    priorityLabel = existingPriority.toUpperCase();
  } else if (explicitPriorityMatch) {
    priorityLabel = explicitPriorityMatch[1].toUpperCase();
  } else if (
    /\b(outage|down|500|broken prod|security|vulnerability)\b/.test(fullText)
  ) {
    priorityLabel = "P0";
  } else if (
    /\b(ci fail|deploy fail|blocking|regression|health check fail)\b/.test(
      fullText,
    )
  ) {
    priorityLabel = "P1";
  } else if (isCleanup || complexityLevel === "easy") {
    priorityLabel = "P3";
  } else {
    priorityLabel = "P2";
  }

  let autonomyLabel;
  let autonomyRationale;
  let isAutoPickupEligible;
  if (isOneWayDoor) {
    autonomyLabel = "needs-human";
    autonomyRationale = `One-way door detected (${matchedOneWay.join(", ")}). Requires explicit human consultation.`;
    isAutoPickupEligible = false;
  } else if (complexityLevel === "hard") {
    autonomyLabel = "needs-human";
    autonomyRationale = `Two-way door with hard complexity (${matchedHard.join(", ")}). Requires human architecture review first.`;
    isAutoPickupEligible = false;
  } else if (
    body.trim().length < 40 &&
    !title.toLowerCase().startsWith("fix")
  ) {
    autonomyLabel = "needs-human";
    autonomyRationale = `Issue specification is brief (${body.trim().length} chars). Requires clearer acceptance criteria.`;
    isAutoPickupEligible = false;
  } else {
    autonomyLabel = "agent-ready";
    autonomyRationale = `Two-way door with ${complexityLevel} complexity. Bounded blast radius and verifiable via \`${verifyCommand}\`.`;
    isAutoPickupEligible = !isCleanup || isCleanupReady;
  }

  const labelDefinitions = [
    { name: "P0", color: "b60205", description: "Broken or blocking now; work next" },
    { name: "P1", color: "d93f0b", description: "High priority regression or friction" },
    { name: "P2", color: "fbca04", description: "Normal priority work" },
    { name: "P3", color: "c5def5", description: "Low priority or deferred cleanup" },
    { name: "agent-ready", color: "0e8a16", description: "Defined requirements and deterministic verification" },
    { name: "needs-human", color: "fbca04", description: "Requires human decision before implementation" },
    { name: "two-way-door", color: "1d76db", description: "Easily reversible change" },
    { name: "one-way-door", color: "b60205", description: "Irreversible or high blast-radius change" },
    { name: "complexity:easy", color: "0e8a16", description: "Isolated fix or cleanup" },
    { name: "complexity:moderate", color: "fbca04", description: "Contained multi-file change" },
    { name: "complexity:hard", color: "d93f0b", description: "Cross-cutting or deep architectural change" },
    { name: "friction", color: "e99695", description: "Operational or developer-experience friction" },
    { name: "friction-skipped", color: "ededed", description: "Friction intentionally deferred" },
    { name: "cleanup", color: "c5def5", description: "Deferred cleanup with Ready when + How to verify" },
    { name: "cleanup-ready", color: "0e8a16", description: "Cleanup prerequisite verified; safe for removal PR" },
  ];

  for (const lbl of labelDefinitions) {
    try {
      await github.rest.issues.createLabel({
        owner: context.repo.owner,
        repo: context.repo.repo,
        name: lbl.name,
        color: lbl.color,
        description: lbl.description,
      });
    } catch {
      // Label already exists
    }
  }

  const labelsToApply = [
    doorLabel,
    autonomyLabel,
    complexityLabel,
    priorityLabel,
  ];
  if (isFriction) labelsToApply.push("friction");
  if (isCleanup) labelsToApply.push("cleanup");

  try {
    await github.rest.issues.addLabels({
      owner: context.repo.owner,
      repo: context.repo.repo,
      issue_number: context.issue.number,
      labels: [...new Set(labelsToApply)],
    });
  } catch (err) {
    if (core && core.warning) {
      core.warning(`Failed to add labels: ${err.message}`);
    }
  }

  const issueTypeLabel = isCleanup
    ? "cleanup"
    : isFriction
      ? "friction"
      : "standard";
  const autoPickupCell =
    isCleanup && !isCleanupReady
      ? "**HELD FOR CLEANUP VERIFICATION**"
      : isAutoPickupEligible
        ? "**AUTO-PICKUP ACTIVE**"
        : "**AWAITING HUMAN DECISION**";
  const autoPickupNote =
    isCleanup && !isCleanupReady
      ? "Deferred cleanup issue: held until `How to verify` passes (`cleanup-ready`)."
      : isAutoPickupEligible
        ? "Two-way door of easy/moderate complexity; prioritized P0–P3. Medium/high PRs require independent `review-pr` subagent."
        : `Paused for \`${owner}\` decision (\`needs-human\`).`;

  const commentBody = `### Automated Issue Triage

| Dimension | Classification | Assessment & Rationale |
| :--- | :--- | :--- |
| **Issue Type** | \`${issueTypeLabel}\` | ${isCleanup ? "Deferred cleanup" : isFriction ? "Friction report" : "Feature or defect"} |
| **Priority** | \`${priorityLabel}\` | Ranked order: \`P0 > P1 > P2 > P3\` |
| **Door** | \`${doorLabel}\` | ${doorTypeDesc} |
| **Complexity** | \`${complexityLabel}\` | ${complexityRationale} |
| **Autonomy** | \`${autonomyLabel}\` | ${autonomyRationale} |
| **Pickup** | ${autoPickupCell} | ${autoPickupNote} |

#### Next Action
${
  isCleanup && !isCleanupReady
    ? "Remains paused until `Ready when` / `How to verify` passes and `cleanup-ready` is applied."
    : isAutoPickupEligible
      ? `Ready for pickup (\`${priorityLabel}\`): verify with \`${verifyCommand}\` and open a PR (never auto-merged without explicit policy/approval).`
      : `Awaiting \`${owner}\` decision (\`needs-human\`) before implementation.`
}
`;

  await github.rest.issues.createComment({
    owner: context.repo.owner,
    repo: context.repo.repo,
    issue_number: context.issue.number,
    body: commentBody,
  });
};
