# Tasks

## 1. Review baseline

- [x] 1.1 Review the three spec deltas against current behavior of the public menu, order intake and payment methods; verify every scenario matches the code (fix the spec, not the code, on mismatch)
- [x] 1.2 Run `openspec validate baseline-public-order-flow --strict` and verify it passes

## 2. Publish baseline

- [x] 2.1 Archive the change with `/opsx:archive` and verify `openspec list --specs` shows `public-store-checkout`, `order-intake` and `payment-methods`
