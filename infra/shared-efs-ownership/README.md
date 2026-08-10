# Shared production EFS ownership import

This isolated Terraform root adopts the exact production EFS resources retained
from Elastic Beanstalk. Five static Terraform 1.5+ import blocks bind every
existing physical resource before Terraform can manage it. Planning fails until
the approval, manifest, detach, and literal confirmation gates are supplied.
It also verifies the active AWS caller is account `850335719356`; never rely on
the shell's default profile.

Exact import manifest:

| Resource | Physical ID | Network identity |
| --- | --- | --- |
| EFS file system | `fs-4243883b` | `us-east-2`, account `850335719356` |
| Mount-target security group | `sg-26c1f14c` | `vpc-d90971b0` |
| Mount target 2a | `fsmt-994a81e0` | `subnet-49a4ea20`, `172.31.11.70` |
| Mount target 2b | `fsmt-a74a81de` | `subnet-e1f7629a`, `172.31.25.49` |
| Mount target 2c | `fsmt-a44a81dd` | `subnet-8377a9ce`, `172.31.36.220` |

The IDs were cross-checked through read-only EC2 ENI and resource-tagging APIs.
An authorized EFS/CloudFormation inventory is still required before activation.
See `docs/operations/HEC-PHASE2-DECOMMISSION.md` for the two-phase retain,
detach, import, no-change plan, and production verification procedure.

Do not point another Terraform state at these physical objects. HashiCorp's
import contract expects each remote object to be bound to exactly one resource
address. The dedicated state key is
`hectv-wp/shared-efs/terraform.tfstate`; the production and staging states keep
only their access points and standalone ingress rules.
