# HEC Phase 2 WordPress staging and shared-EFS decommission

**Status:** implementation preparation only; no AWS, DNS, database, secret, or
GitHub-environment mutation is authorized by this document or its pull request.

The controlling plan is `ytadvisors/openclaw#1505`. Its live approval and action
record is the internal Phase 2 execution playbook. Stop whenever an identifier,
dependency, approval, or expected plan action is missing or different.

## Exact retain boundary

- EFS file system `fs-4243883b`.
- EFS mount-target security group `sg-26c1f14c` in `vpc-d90971b0`.
- Mount targets `fsmt-994a81e0`, `fsmt-a74a81de`, and `fsmt-a44a81dd`.
- Production EFS access point `fsap-01d12e2b64e0a3600`.
- Aurora cluster `hectv-db-cluster`, production ECS/ALB, ACM certificate, DNS,
  state bucket, and lock table.

Read-only EC2 ENI inventory associated the three mount targets with these exact
network identities:

| AZ | Mount target | Subnet | Private IP |
| --- | --- | --- | --- |
| `us-east-2a` | `fsmt-994a81e0` | `subnet-49a4ea20` | `172.31.11.70` |
| `us-east-2b` | `fsmt-a74a81de` | `subnet-e1f7629a` | `172.31.25.49` |
| `us-east-2c` | `fsmt-a44a81dd` | `subnet-8377a9ce` | `172.31.36.220` |

The current read-only role still cannot call EFS or CloudFormation Describe.
An authorized administrator must independently confirm these IDs, export the
live stack template/resources, and capture the EFS configuration before GO.

## Gate A — produce and review the staging destroy plan

Do not delete Terraform state. Preserve the current S3 object version and its
checksum before planning. Initialize only against the exact staging backend key
`hectv-wp/staging/terraform.tfstate`, then create a binary destroy plan without
applying it:

```bash
terraform -chdir=infra/staging init -backend-config=backend.hcl
terraform -chdir=infra/staging plan -destroy -out=/approved/evidence/staging-destroy.tfplan
terraform -chdir=infra/staging show -json /approved/evidence/staging-destroy.tfplan \
  > /approved/evidence/staging-destroy.json
bash scripts/decommission/verify-staging-destroy-plan.sh \
  /approved/evidence/staging-destroy.json
```

The verifier accepts managed `delete` actions only, rejects any address absent
from `infra/staging/main.tf`, and requires the two services, staging ALB/DNS,
staging EFS access point, ECR repository, and staging-only Aurora/EFS ingress
rules. It does not prove backups, database/user deletion, Cognito dependencies,
secret recovery windows, or task-definition cleanup; those remain separate
approved actions outside Terraform.

Hash the binary and JSON plans, obtain independent exact-plan review, and record
the hashes in the execution playbook. Apply only that saved binary plan during
the approved window. A refreshed or different plan requires new review.

## Gate B — retain before Beanstalk removal

The live Beanstalk CloudFormation stack currently owns `FileSystem`,
`MountTargetA`, `MountTargetB`, `MountTargetC`, and
`MountTargetSecurityGroup`. Source now assigns both `DeletionPolicy: Retain` and
`UpdateReplacePolicy: Retain` to all five, but merging source is not deployment
and does not change the live stack.

Before any Beanstalk quiescence or termination, an authorized administrator
must:

1. export the live stack template and resource list;
2. apply the reviewed retain-policy change through the authoritative control
   plane;
3. export the template again and prove all five live logical resources carry
   both retain policies;
4. hash that receipt and record it with the approved manifest; and
5. confirm production tasks can read and write the expected media path.

If Beanstalk cannot safely apply the source change, stop. A direct stack change
requires a separately reviewed orphan-stack procedure; never delete child
resources individually.

## Gate C — detach and import ownership

`infra/shared-efs-ownership` uses a dedicated remote-state key. Five static
Terraform 1.5+ import blocks bind the exact existing EFS, security group, and
three mount targets; a missing physical ID fails planning rather than creating
a replacement. Every managed object has `prevent_destroy`. Planning also fails
without a positive approval task ID, SHA-256 receipts for the approved manifest
and CloudFormation detach evidence, and the literal confirmation
`IMPORT RETAINED HEC EFS`. The root verifies the active caller is HEC account
`850335719356`; the shell default profile must never be trusted.

Sequence:

1. verify CloudFormation retain policies are live;
2. remove the retained resources from Beanstalk ownership through the reviewed
   authoritative stack/environment operation;
3. prove the resources and production path remain healthy;
4. initialize `infra/shared-efs-ownership` against
   `hectv-wp/shared-efs/terraform.tfstate`;
5. set the three receipt values and exact import confirmation;
6. run a saved plan and require exactly five imports with zero creates,
   updates, replacements, or deletes;
7. independently review that plan and only then apply it to record ownership;
8. run a second plan and require `No changes`;
9. verify all production ECS tasks, ALB health, GraphQL, REST, media reads, a
   controlled admin media write/remove, EFS metrics, and recovery evidence.

HashiCorp documents that imports bind existing objects into state and that one
remote object must not be bound to multiple resource addresses. Do not apply
the import while CloudFormation still manages the same physical resources.

## Gate D — Beanstalk cleanup

Only after Gate C and the approved observation interval may the authoritative
Beanstalk stack path remove the ASG, its two EC2 instances, Classic ELB, and
EB-only security groups. Retain the shared EFS resources, shared ACM certificate,
Aurora, production ECS, service bucket, and unknown second EB stack. If the EB
API/stack state disagrees or production regresses, stop and execute the recorded
rollback decision; never improvise child-resource deletion.
