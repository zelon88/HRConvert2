def show_polyscope(self):
    # Shows a polyscope window (https://polyscope.run/py/) of the current MeshSet, rendering all the meshes contained
    # in it. Requires the polyscope package (pip install polyscope).
    import polyscope
    import numpy

    polyscope.init()
    for m in self:
        is_enabled = m.is_visible()
        if m.is_point_cloud():
            psm = polyscope.register_point_cloud(m.label(), m.transformed_vertex_matrix(), enabled=is_enabled)
        else:
            psm = polyscope.register_surface_mesh(m.label(), m.transformed_vertex_matrix(), m.face_matrix(), enabled=is_enabled)
        if m.has_vertex_color():
            vc = m.vertex_color_matrix()
            vc = numpy.delete(vc, 3, 1)
            psm.add_color_quantity('vertex_color', vc)
        if m.has_vertex_scalar():
            psm.add_scalar_quantity('vertex_scalar', m.vertex_scalar_array())
        if not m.is_point_cloud() and m.has_face_color():
            fc = m.face_color_matrix()
            fc = numpy.delete(fc, 3, 1)
            psm.add_color_quantity('face_color', fc, defined_on='faces')
        if not m.is_point_cloud() and m.has_face_scalar():
            psm.add_scalar_quantity('face_scalar', m.face_scalar_array(), defined_on='faces')
    polyscope.show()

