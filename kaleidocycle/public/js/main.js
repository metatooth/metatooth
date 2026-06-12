import * as THREE from "three";
import { OrbitControls } from "three/addons/controls/OrbitControls.js";

const frustum = 1000;
const aspect = window.innerWidth / window.innerHeight;

const scene = new THREE.Scene();
const camera = new THREE.OrthographicCamera(
  (frustum * aspect) / -2,
  (frustum * aspect) / 2,
  frustum / 2,
  frustum / -2,
  1,
  1000,
);
camera.up = new THREE.Vector3(0, 0, 1);
camera.lookAt(0, 0, 0);
camera.zoom = 100;
camera.updateProjectionMatrix();

scene.add(camera);

const renderer = new THREE.WebGLRenderer();
renderer.setPixelRatio(window.devicePixelRatio);
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setClearColor(0x000000, 1);
document.body.appendChild(renderer.domElement);

const controls = new OrbitControls(camera, renderer.domElement);
controls.enablePan = false;
controls.enableDamping = true;
controls.dampingFactor = 0.25;
controls.screenSpacePanning = false;
controls.minDistance = 100;
controls.maxDistance = 500;
controls.maxPolarAngle = 2 * Math.PI;

const lights = [];
lights[0] = new THREE.AmbientLight(0xfdfdfd, 0.25);
lights[1] = new THREE.DirectionalLight(0xff33bb, 1.0);
lights[1].position.set(-100, 0, 100);
lights[2] = new THREE.DirectionalLight(0xfdfdfd, 1.0);
lights[2].position.set(100, 0, -100);
scene.add(lights[0]);
scene.add(lights[1]);
scene.add(lights[2]);

// Parametric kaleidocycle vertex computation.
// Reference: https://intothecontinuum.tumblr.com/post/50873970770
// n=8 tetrahedra → a = 2π/8 = π/4, tan(a) = 1.
// 4 pairs × 2 tetrahedra (identity + reflection) = 8 total.
function baseVertices(t) {
  const s = Math.sin(t);
  const c = Math.cos(t);
  const d = Math.sqrt(1 + s * s);

  const w1 = [c, 0, s];
  const w2 = [-s / d, -s / d, c / d];
  const w3 = [-s * s / d, 1 / d, s * c / d];

  const P = [w3[1] - w3[0], 0, -w3[2] / 2];
  const Q = [w3[1], w3[1], w3[2] / 2];

  const h = Math.SQRT2 / 2;
  return [
    [P[0] - h * w1[0], P[1] - h * w1[1], P[2] - h * w1[2]],
    [P[0] + h * w1[0], P[1] + h * w1[1], P[2] + h * w1[2]],
    [Q[0] - h * w2[0], Q[1] - h * w2[1], Q[2] - h * w2[2]],
    [Q[0] + h * w2[0], Q[1] + h * w2[1], Q[2] + h * w2[2]],
  ];
}

function rotZ(v, alpha) {
  const ca = Math.cos(alpha), sa = Math.sin(alpha);
  return [v[0] * ca - v[1] * sa, v[0] * sa + v[1] * ca, v[2]];
}

const SCALE = 2.0;

function tetraVertices(t, pairIndex, reflected) {
  const alpha = pairIndex * (Math.PI / 2);
  return baseVertices(t).map((v) => {
    const vp = reflected ? [v[1], v[0], v[2]] : v;
    return rotZ(vp, alpha).map((x) => x * SCALE);
  });
}

function facePositions([V0, V1, V2, V3]) {
  return new Float32Array([
    ...V0, ...V1, ...V2,
    ...V0, ...V2, ...V3,
    ...V0, ...V3, ...V1,
    ...V1, ...V3, ...V2,
  ]);
}

function edgePositions([V0, V1, V2, V3]) {
  return new Float32Array([
    ...V0, ...V1,
    ...V0, ...V2,
    ...V0, ...V3,
    ...V1, ...V2,
    ...V1, ...V3,
    ...V2, ...V3,
  ]);
}

const meshMaterial = new THREE.MeshPhongMaterial({
  color: 0x00bbee,
  specular: 0xfdfdfd,
  shininess: 40,
  side: THREE.DoubleSide,
});
const wireMaterial = new THREE.LineBasicMaterial({
  color: 0xff33bb,
  transparent: true,
  opacity: 0.5,
});

const tetras = [];

for (let r = 0; r < 4; r++) {
  for (let ref = 0; ref < 2; ref++) {
    const reflected = ref === 1;
    const verts = tetraVertices(0, r, reflected);

    const meshGeo = new THREE.BufferGeometry();
    meshGeo.setAttribute("position", new THREE.BufferAttribute(facePositions(verts), 3));
    meshGeo.computeVertexNormals();

    const wireGeo = new THREE.BufferGeometry();
    wireGeo.setAttribute("position", new THREE.BufferAttribute(edgePositions(verts), 3));

    scene.add(new THREE.Mesh(meshGeo, meshMaterial));
    scene.add(new THREE.LineSegments(wireGeo, wireMaterial));

    tetras.push({ meshGeo, wireGeo, r, reflected });
  }
}

function update(t) {
  for (const { meshGeo, wireGeo, r, reflected } of tetras) {
    const verts = tetraVertices(t, r, reflected);

    meshGeo.attributes.position.array.set(facePositions(verts));
    meshGeo.attributes.position.needsUpdate = true;
    meshGeo.computeVertexNormals();

    wireGeo.attributes.position.array.set(edgePositions(verts));
    wireGeo.attributes.position.needsUpdate = true;
  }
}

let theta = 0;
let twist = 0.01;
let stop = false;

document.addEventListener("keydown", (e) => {
  if (e.keyCode === 83) {
    stop = !stop;
  } else if (e.keyCode === 84) {
    twist *= -1;
  }
});

const animate = function () {
  requestAnimationFrame(animate);
  controls.update();
  if (!stop) {
    theta += twist;
    update(theta);
  }
  renderer.render(scene, camera);
};

animate();
