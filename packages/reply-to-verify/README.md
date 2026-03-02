# ReplyToVerify — Lightweight Auth Trait (Opt-In)

ReplyToVerify is an optional, lightweight authentication extension that can be implemented as a trait or small support class alongside Laravel’s existing authentication features.

Instead of requiring users to click a verification link, the system sends a short one-time code and asks the user to reply to the email.

When a signed inbound webhook receives the reply and validates the code, the user is verified.

It does **not** replace Laravel’s built-in email verification.  
It simply adds an alternative, higher-engagement verification strategy.

---

## Why Add It as a Trait or Class?

Laravel already organizes authentication logic using:

- Traits
- Small service classes
- Modular components
- Explicit opt-in behavior

ReplyToVerify fits naturally into that structure.

It can be implemented as:

- A trait added to the `User` model  
- Or a small service class used inside registration logic  

No framework core changes are required.

This keeps it consistent with existing auth architecture.

---

## Why This Matters

### Better Deliverability
Replies create strong engagement signals for mailbox providers.

That improves inbox placement for:
- Billing notifications
- Usage alerts
- AI model outputs
- Security updates

### Stronger Anti-Abuse Protection
Automated bots can click verification links easily.

Reply-based verification requires:
- Real SMTP interaction
- Provider signature validation
- A functioning mailbox

That significantly increases abuse cost.

### Valuable For AI Startups
For AI-first applications — especially in competitive markets like the US and China — this approach provides:

- Protection against API credit farming
- Higher trust signals
- Early user intent data (if optionally captured)

---

## How It Works (Concept)

1. User registers.
2. Application generates a short numeric code.
3. Email is sent:

   > Reply “394812” to verify your account.

4. Email provider sends inbound message to a webhook.
5. The system:
   - Validates the provider signature
   - Matches the `From` email
   - Checks the code
   - Marks `email_verified_at`

After successful verification:
- The code is invalidated.
- The account becomes verified.

---

## Security Requirements

Any implementation must:

- Validate provider webhook signatures.
- Require exact email match with registered user.
- Invalidate the code after first successful use.
- Rate-limit inbound attempts.
- Return `200 OK` on non-matching attempts to prevent retry loops.

Storing the full reply body should remain optional and explicitly configured.

---

## Why It Fits the Framework

Laravel already includes authentication helpers that are:

- Small
- Reusable
- Optional
- Composable

ReplyToVerify follows the same philosophy by existing as:

- A trait
- Or a lightweight class
- Not a framework-level feature

It keeps responsibility inside the application layer while improving authentication flexibility.

---

## Intended Audience

- AI startups protecting API usage
- Apps fighting automated account creation
- Products needing stronger deliverability signals
- Systems that want higher-intent verification

It is not meant to be required for standard applications.

---

## Status

Proposed as a minimal, opt-in auth extension that can live as a trait or service class alongside other authentication components without modifying core behavior.

Feedback on placement and structure is welcome.
