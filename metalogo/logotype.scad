R=1; // radius of circle inscribed by smallest triangle

A=60; // equilateral triangle included angle
a=2*R*sin(A); // equilateral triangle side
h=sqrt(3)*a/2; // equilateral triangle height

radius=4*R; // inscribe largest triangle

L=65; // magic length
H=9; // height of webbing
D=2; // depth of webbing
d=5; // depth of lettering

ratio=1.05; // make final sphere a little bit bigger

union() {
     linear_extrude(d) {
          text("Metatooth", font = "EB Garamond:style=08 Regular");
     }

     polyhedron(
          points = [[0,0,0],[L,0,0],[L,H,0],[0,H,0],
                    [0,0,D],[L,0,D],[L,H,D],[0,H,D]],
          faces = [[0,1,2,3],[4,5,1,0],[7,6,5,4],
                   [5,6,2,1],[6,7,3,2],[7,4,0,3]]
          );
}

translate([L-d,1.05*radius,ratio*radius+d/2]) {
     rotate(45, [0,0,1]) {
          rotate(180, [1,0,0]) {
               difference() {
                    translate([0,0,ratio*radius]) {
                         sphere(ratio*radius, $fn=100);

                    }

                    linear_extrude(radius) {
                         circle(radius, $fn=3);
                    }


               }

               for (i = [0:2]) {
                    intersection() {
                         linear_extrude(radius) {
                              translate([-R,a,0]) {
                                   rotate(180, [0,0,1]) {
                                        translate([0,i*a,0]) {
                                             circle(R, $fn=3);
                                        }
                                   }
                              }
                         }
                         translate([0,0,1.05*radius]) {
                              sphere(1.05*radius, $fn=100);
                         }
                    }
               }

               for (i = [0:1]) {
                    intersection() {
                         linear_extrude(radius) {
                              translate([h-R,a/2,0]) {
                                   rotate(180, [0,0,1]) {
                                        translate([0,i*a,0]) {
                                             circle(R, $fn=3);
                                        }
                                   }
                              }
                         }
                         translate([0,0,1.05*radius]) {
                              sphere(1.05*radius, $fn=100);
                         }
                    }
               }

               for (i = [0:0]) {
                    intersection() {
                         linear_extrude(radius) {
                              translate([2*h-R,0,0]) {
                                   rotate(180, [0,0,1]) {
                                        translate([0,i*a,0]) {
                                             circle(R, $fn=3);
                                        }
                                   }
                              }
                         }
                         translate([0,0,1.05*radius]) {
                              sphere(1.05*radius, $fn=100);
                         }
                    }
               }
          }
     }
}
