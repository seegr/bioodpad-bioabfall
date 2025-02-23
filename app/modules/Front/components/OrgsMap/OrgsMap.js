import lkGeo from "./lk-geo.json";
import lkPolygon from "./lg-polygon.json";

export default function initOrgsMap() {
  const wrap = document.getElementById("orgs-map")

  if (!wrap) return

  const libereckyKrajBounds = {
    north: 51.056,
    south: 50.254,
    west: 14.612,
    east: 15.480,
  };

  const map = new google.maps.Map(wrap, {
    center: {lat: 50.7374, lng: 15.04},
    zoom: 9,
    mapTypeControl: false,
    restriction: {
      latLngBounds: libereckyKrajBounds,
      strictBounds: false,
    },
  });

  // map.data.addGeoJson(lkGeo);

  // map.data.setStyle({
  //   fillColor: 'green',
  //   fillOpacity: 0.1,
  //   strokeWeight: 2,
  //   strokeColor: 'black',
  //   visible: true,
  // });

  console.log(lkPolygon)
  const libereckyKrajPolygons = lkPolygon.coordinates.map(polygonGroup => {
    const polygon = polygonGroup[0].map(coord => ({lat: coord[1], lng: coord[0]}));

    const firstPoint = polygon[0];
    const lastPoint = polygon[polygon.length - 1];

    if (firstPoint.lat !== lastPoint.lat || firstPoint.lng !== lastPoint.lng) {
      polygon.push({lat: firstPoint.lat, lng: firstPoint.lng});
    }

    return polygon;
  });

  const outerPolygon = [
    {lat: 70, lng: 80},
    {lat: 70, lng: -80},
    {lat: 10, lng: -80},
    {lat: 10, lng: 80}
  ];

  new google.maps.Polygon({
    paths: [outerPolygon, ...libereckyKrajPolygons],
    strokeColor: "#ffffff",
    strokeOpacity: 1,
    strokeWeight: 1,
    fillColor: "#FFFFFF",
    fillOpacity: 0.85,
    map: map
  });

  const icon = {
    url: "dist/front/images/map-pin.png",
    scaledSize: new google.maps.Size(50, 50),
    origin: new google.maps.Point(0, 0),
    anchor: new google.maps.Point(25, 50)
  };


  const orgs = JSON.parse(wrap.dataset.orgs)

  Object.entries(orgs).forEach(([id, org]) => {
    if (!org.lat || !org.lng) return

    const marker = new google.maps.Marker({
      position: {
        lat: org.lat,
        lng: org.lng
      },
      map: map,
      icon: icon,
      title: org.title
    });

    const infoWindow = new google.maps.InfoWindow({
      content: `
        <div style="width:200px;">
            <h3 class="font-bold">${org.title}</h3>
            <p>${org.address}</p>
            <br>
            <p>
                <a href="${org.link}"
                    target="_blank"
                >${org.link}</a>
            </p>
        </div>
        `,
    });

    marker.addListener('click', () => {
      infoWindow.open({
        anchor: marker,
        map,
        shouldFocus: false,
      });
    });
  })
}
