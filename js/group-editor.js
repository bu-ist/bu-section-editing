jQuery(document).ready(function(b){var l=b("#group-member-list");b("a.nav-link").click(function(a){a.preventDefault();var a=b("a.nav-tab[href="+this.hash+"]"),c=b(this.hash);$input=c.hasClass("group-panel")?b("#tab"):b("#perm_panel");$input.val(a.data("target"));a.addClass("nav-tab-active").siblings().removeClass("nav-tab-active");c.addClass("active").siblings().removeClass("active")});b("#edit-group-name").blur(function(){b("#group-stats-name").html(b(this).val())});b(".member:not(.active)").appendTo("#inactive-members");
l.delegate("a.remove_member","click",function(a){a.preventDefault();b(this).parent(".member").removeClass("active").slideUp("fast",function(){b(this).appendTo("#inactive-members").find('input[type="checkbox"]').removeAttr("checked");p()})});b("#find_user").click(function(a){var c={action:"buse_find_user",user:b("#user_login").val()};b.ajax({url:ajaxurl,data:c,cache:!1,type:"POST",success:function(){},error:function(){}});a.preventDefault()});b("#add_member").bind("click",function(b){b.preventDefault();
q()});b("#user_login").keypress(function(b){"13"==b.keyCode&&(b.preventDefault(),q())});var q=function(){var a={action:"buse_add_member",group_id:b("#group_id").val(),user:b("#user_login").val()};b.ajax({url:ajaxurl,data:a,cache:!1,type:"POST",success:function(c){if(c.status){var d=a.user;b('.member input[value="'+c.user_id+'"]').is(":checked")?b("#members-message").attr("class","error").html("<p>"+d+" has already been added to the group member list.</p>").fadeIn():(b("#members-message").fadeOut("fast",
function(){b(this).attr("class","").html("")}),b("#member_"+c.user_id).attr("checked","checked").parent(".member").addClass("active").appendTo(l).slideDown("fast"),p())}else b("#members-message").attr("class","error").html(c.message).fadeIn()},error:function(){}});b("#user_login").val("").focus()},p=function(){var a=l.children(".member").length;b(".member-count").html(a)},v=function(a){var c=a.find(".perm-editor").first();if(c.hasClass("hierarchical")){var d=c.data("post-type");r.json_data={ajax:{url:ajaxurl,
type:"GET",cache:!1,data:function(c){return{action:"buse_render_post_list",group_id:b("#group_id").val(),post_type:d,query:{child_of:c.attr?c.attr("id").substr(1):0}}}}};c.bind("loaded.jstree",function(c,a){!0==b.browser.msie&&8>parseInt(b.browser.version,10)||b(this).find("ul > .jstree-closed").each(function(){var c=b(this);a.inst.load_node(b(this),function(){s(c)})})}).bind("select_node.jstree",function(b,a){a.inst.is_selected(a.rslt.obj)&&t(a.rslt.obj,c)}).bind("deselect_node.jstree",function(){h(c)}).bind("deselect_all.jstree",
function(){h(c)}).bind("overlay_clicked.buse",function(){c.jstree("get_selected").each(function(){u(b(this),c);c.jstree("deselect_node",b(this))})}).jstree(r);c.closest(".perm-panel").bind("click",function(a){!b(a.target).hasClass("jstree-clicked")&&!b(a.target).parents(".jstree-clicked").length&&c.jstree("get_selected").length&&c.jstree("deselect_all")})}else{var e=c.data("post-type");c.delegate("a","click",function(a){a.preventDefault();a.stopPropagation();$post=b(this).parent("li").first();$post.siblings("li.perm-item-selected").each(function(){b(this).removeClass("perm-item-selected");
c.trigger("deselect_post.buse",{post:b(this)})});$post.addClass("perm-item-selected");c.trigger("select_post.buse",{post:$post})});c.closest(".perm-panel").bind("click",function(){c.trigger("deselect_all.buse")});c.bind("posts_loaded.buse",function(){var a=b(this).siblings("input.buse-edits").first();if(0!==a.val().length)for(post_id in a=JSON.parse(a.val()),a){var d=c.find("#p"+post_id);d.length&&d.attr("rel",a[post_id])}}).bind("select_post.buse",function(b,a){t(a.post,c)}).bind("deselect_all.buse",
function(){h(c)}).bind("overlay_clicked.buse",function(){c.find(".perm-item-selected").each(function(){u(b(this),c);b(this).removeClass("perm-item-selected")})});m(c,{post_type:e})}a.delegate("button.perm-search","click",function(a){a.preventDefault();a=b(this).siblings("input").first().val();a={post_type:c.data("post-type"),query:{s:a}};h(c);m(c,a)});a.delegate("select.perm-sort","change",function(a){a.preventDefault();var d=b(this).val().split(":"),a=d[0],d=d[1]||"DESC",a={post_type:c.data("post-type"),
query:{orderby:a,order:d}};h(c);m(c,a)});a.delegate("input.perm-search","keypress",function(a){13==a.keyCode&&(a.preventDefault(),b(this).siblings("button").first().click())});a.delegate("a.perm-tree-expand","click",function(a){a.preventDefault();b.jstree._reference(c).open_all()});a.delegate("a.perm-tree-collapse","click",function(a){a.preventDefault();b.jstree._reference(c).close_all()});b('<span class="buse-overlay inactive"></span>').html('<a href="#" class="buse-action"><ins class="buse-icon">&nbsp;</ins></a>').insertAfter(c).delegate(".buse-action",
"click",function(a){a.stopPropagation();a.preventDefault();h(c);c.trigger("overlay_clicked.buse")});a.addClass("loaded")};b("#perm-tab-container").delegate("a","click",function(){var a=b(b(this).attr("href"));a.hasClass("loaded")||v(a)});var z={allowed:{label:"Deny Editing","class":"allowed"},"allowed-desc-denied":{label:"Deny Editing","class":"allowed"},denied:{label:"Allow Editing","class":"denied"},"denied-desc-allowed":{label:"Allow Editing","class":"denied"}},t=function(a,c){var d=a.attr("rel"),
e=a.children("a:first").first(),f=b("#perm-panel-container"),i=c.siblings(".buse-overlay").first(),d=z[d],e={of:e,my:"left center",at:"right center",within:f,offset:"15 0",collision:"fit none"};i.find(".buse-action").html('<ins class="buse-icon">&nbsp;</ins> '+d.label);i.removeClass("inactive allowed allowed-desc-denied denied denied-desc-allowed").addClass(d["class"]).position(e)},h=function(a){a.siblings(".buse-overlay").removeClass("allowed allowed-desc-denied denied denied-desc-allowed").addClass("inactive")[0].removeAttribute("style")},
r={plugins:["themes","types","json_data","ui"],core:{animation:0,html_titles:!0},themes:{theme:"classic"},types:{types:{"default":{clickable:!0,renameable:!1,deletable:!1,creatable:!1,draggable:!1,max_children:-1,max_depth:-1,valid_children:"all",icon:{image:buse_config.pluginUrl+"/images/group_perm_denied.png"}},denied:{clickable:!0,renameable:!1,deletable:!1,creatable:!1,draggable:!1,max_children:-1,max_depth:-1,valid_children:"all",icon:{image:buse_config.pluginUrl+"/images/group_perm_denied.png"}},
"denied-desc-allowed":{clickable:!0,renameable:!1,deletable:!1,creatable:!1,draggable:!1,max_children:-1,max_depth:-1,valid_children:"all",icon:{image:buse_config.pluginUrl+"/images/group_perm_denied_desc_allowed.png"}},"denied-desc-unknown":{clickable:!0,renameable:!1,deletable:!1,creatable:!1,draggable:!1,max_children:-1,max_depth:-1,valid_children:"all",icon:{image:buse_config.pluginUrl+"/images/group_perm_denied_desc_unknown.png"}},allowed:{clickable:!0,renameable:!1,deletable:!1,creatable:!1,
draggable:!1,max_children:-1,max_depth:-1,valid_children:"all",icon:{image:buse_config.pluginUrl+"/images/group_perm_allowed.png"}},"allowed-desc-denied":{clickable:!0,renameable:!1,deletable:!1,creatable:!1,draggable:!1,max_children:-1,max_depth:-1,valid_children:"all",icon:{image:buse_config.pluginUrl+"/images/group_perm_allowed_desc_denied.png"}},"allowed-desc-unknown":{clickable:!0,renameable:!1,deletable:!1,creatable:!1,draggable:!1,max_children:-1,max_depth:-1,valid_children:"all",icon:{image:buse_config.pluginUrl+
"/images/group_perm_allowed_desc_unknown.png"}}}},ui:{select_limit:1}},m=function(a,c){var d={action:"buse_render_post_list",group_id:b("#group_id").val(),query:{}};void 0!==typeof c&&b.extend(d,c);b.ajax({url:ajaxurl,type:"GET",data:d,cache:!1,success:function(b){d.query.offset?a.append(b):a.html(b);a.trigger("posts_loaded.buse",{posts:b})},error:function(){}})},u=function(a,c){if(c.hasClass("hierarchical")){var d=b.jstree._reference(c);a.hasClass("jstree-closed")?d.open_node(a,function(){d.open_all(a);
n(a);o(a,c)}):(n(a),o(a,c))}c.hasClass("flat")&&(n(a),o(a,c))},n=function(a){switch(a.attr("rel")){case "allowed":case "allowed-desc-denied":a.attr("rel","denied");break;case "denied":case "denied-desc-allowed":a.attr("rel","allowed")}},o=function(a,c){var d=a.attr("id").substr(1),e=a.attr("rel"),f=c.data("post-type"),i,g=b("#buse-edits-"+f).val()||"";i=g?JSON.parse(g):{};var j=0;a.data("perm",e);i[d]=e;j="allowed"==e?j+1:j-1;c.hasClass("hierarchical")&&(a.find("li").each(function(a,c){var d=b(c).data("perm"),
f=b(this).attr("id").substr(1);d!=e&&(b(c).data("perm",e),i[f]=e,j="allowed"==e?j+1:j-1);b(c).attr("rel",e)}),$root_post=a.parentsUntil("#"+c.attr("id"),"li").last(),$root_post.length&&s($root_post));b("#buse-edits-"+f).val(JSON.stringify(i));g=j;b("#group-stats-permissions");var h=b("#"+f+"-stats"),d=b("#"+f+"-pending-diff"),k=0;0==d.length&&(d=b('<span id="'+f+'-pending-diff" class="perm-stats-diff" data-count="0"></span>').appendTo(h));k=parseInt(d.data("count"));f=k+g;g="";0<f?(g=" ( +"+f+" )",
d.removeClass("negative").addClass("positive")):0>f?(g=" ( "+f+" )",d.removeClass("positive").addClass("negative")):d.removeClass("positive negative");d.data("count",f).text(g)},s=function(a){$sections=a.find("ul");$sections.each(function(){var a=b(this).parents("li").first();if(a.length){var d=!1;switch(a.attr("rel")){case "allowed":case "allowed-desc-denied":case "allowed-desc-unknown":(d=0<b(this).find('li[rel="denied"],li[rel="denied-desc-allowed"],li[rel="denied-desc-unknown"]').length)?a.attr("rel",
"allowed-desc-denied"):a.attr("rel","allowed");break;case "denied":case "denied-desc-allowed":case "denied-desc-unknown":(d=0<b(this).find('li[rel="allowed"],li[rel="allowed-desc-denied"],li[rel="allowed-desc-unknown"]').length)?a.attr("rel","denied-desc-allowed"):a.attr("rel","denied")}}})},w,k;w=b("#edit-group-name").val();var x=function(){var a=[];b("#group-member-list").children("li.member.active").each(function(c,d){a.push(b(d).children("input").first().val())});return a};k=x();window.onbeforeunload=
function(){if(A())return"Your group has pending edits.  If you leave now, your changes will be lost."};var A=function(){if(w!=b("#edit-group-name").val())return!0;var a=x();if(k.length!=a.length)return!0;for(index in k)if(-1==b.inArray(k[index],a))return!0;var c=!1;b(".buse-edits").each(function(a,e){b(e).val()&&(c=!0)});return c};b('input[type="submit"]').click(function(){window.onbeforeunload=null});b("a.submitdelete").click(function(a){a.preventDefault();confirm("You are about to permanently delete this section editing group.  This action is irreversible.\n\nAre you sure you want to do this?")&&
(window.onbeforeunload=null,window.location=b(this).attr("href"))});var y=b("#perm-panel-container").find(".perm-panel.active").first();y.length&&v(y)});