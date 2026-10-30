# The 3D Model pipeline

The 3D Model pipeline converts files. It accepts 27 input format(s) & produces 17 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v3.9.6
- **Kind** conversion
- **Family** Model
- **Entry point** convertModels
- **Subsystem** 3D Models
- **Available to** every surface

## Formats

**Accepts** obj, stl, ply, dae, fbx, 3ds, blend, gltf, glb, x3d, off, lwo, ms3d, ac, b3d, q3d, irr, md2, md3, md5mesh, smd, ase, dxf, raw, 3mf, x, ctm

**Produces** obj, stl, ply, dae, fbx, gltf, glb, x3d, off, 3ds, assbin, json, 3mf, x, ctm, dxf, wrl

## Dependencies

- **Assimp** (optional, 5.0 or newer, BSD-3-Clause). Converts between 3D model formats.
- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **MeshLab** (optional, 2020.09 or newer, GPL-3.0). Mesh processing. Only needed when PyMeshLab is disabled.
- **NumPy 3.14** (optional, BSD-3-Clause). Imported by PyMeshLab. On arm pip often has no wheel & compiles it, which takes a long time on a small board.
- **PyMeshLab** (optional, GPL-3.0). Repairs & simplifies meshes. Replaces MeshLab. Ships with HRConvert2.
- **Python 3.14** (optional, 3.14 or newer, PSF-2.0). Loads the bundled PyMeshLab, which is the only mesh route MeshLab has not removed.
- **pip 3.14** (optional, 21.0 or newer, MIT). Installs the python packages this application needs. Bundled with CPython through ensurepip.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
