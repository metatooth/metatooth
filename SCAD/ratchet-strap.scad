scale = 25.4 / 3;

length_x = scale * 17.5;
width_y = scale * 2.9;
height_z = scale * 0.4;

radius = scale * 1.85;
tab_height_y = scale * 0.6;
wall_thickness = scale * 0.3;

rail_width = scale * 0.5;

saw_height = scale * 0.4;
saw_width  = scale * 0.4;
num_teeth = 70;
saw_inset = scale * 0.2;

module sawtooth_pattern()
{
    for (i = [0 : num_teeth - 1])
    {
        polygon(points=[
            [i * saw_width, 0],
            [i * saw_width + saw_width, saw_height],
            [i * saw_width + saw_width, 0]
        ]);
    }
}


translate([0, -2, -1])
{
cube([2, 2, 2]);
}

translate([0, width_y, -1])
{
cube([2, 2, 2]);
}

    // top of the tree

difference()
{
    color("#ffbbee")
    {
        union()
        {
            cube([length_x, width_y, height_z]);
    
            difference()
            {
                translate([0, width_y/2, height_z - tab_height_y])
                {
                    cylinder(tab_height_y, radius, radius, $fn = 64);
                }
                
                color("#0000FF")
                {
                    translate([0, width_y/2, -height_z])
                    {
                        cylinder(height_z, radius - wall_thickness, radius - wall_thickness, $fn = 64);
                    }
                }
                
                translate([0, 0, -1])
{
    cube([radius + wall_thickness, width_y, 1]);
}
            }
        }    
    }
    
    union()
    {

translate([0, -width_y/2, height_z + saw_inset])
{
rotate([270, 0, 0])
{
linear_extrude(2 * width_y)
{
     sawtooth_pattern();
}
}
}

translate([0, 0, 3* height_z / 4])
{
    cube([length_x, rail_width, height_z / 2]);
    
    translate([0, width_y - rail_width, 0])
    {
        cube([length_x, rail_width, height_z / 2]);
    }
}
}

translate([0, -2, -1])
{
cube([2, 2, 2]);
}

translate([0, width_y, -1])
{
cube([2, 2, 2]);
}

}
