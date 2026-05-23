const wcceinfo = {
  'name':'智创云享',
  'version': '5.0.15',
  'siteurl': 'http://localhost:8080/',
};
var title=document.getElementsByTagName('title');
if(title.length > 0) {
  title[0].innerText=wcceinfo.name;
}
