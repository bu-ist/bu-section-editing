jQuery(document).ready(function(b){var p;"undefined"!==typeof bu&&("undefined"!==typeof bu.plugins.navigation&&"undefined"!==typeof bu.plugins.navigation.tree)&&(p=bu.plugins.navigation);var q=b("#group-member-list");b("a.nav-link").click(function(a){a.preventDefault();a=b("a.nav-tab[href="+this.hash+"]");var d=b(this.hash);$input=d.hasClass("group-panel")?b("#tab"):b("#perm_panel");$input.val(a.data("target"));a.addClass("nav-tab-active").siblings().removeClass("nav-tab-active");d.addClass("active").siblings().removeClass("active")});
b("#edit-group-name").blur(function(){var a=b.trim(b(this).val());60<a.length&&(a=a.slice(0,59),b(this).val(a));if(1>a.length)return k(buse_group_editor_settings.nameRequiredNotice),!1;s();b("#group-stats-name").html(a)});b(".member:not(.active)").appendTo("#inactive-members");q.delegate("a.remove_member","click",function(a){a.preventDefault();b(this).parent(".member").removeClass("active").slideUp("fast",function(){b(this).appendTo("#inactive-members").find('input[type="checkbox"]').removeAttr("checked");
t()})});var u=function(a){var d=b.map(b('li.member.active input[type="checkbox"]'),function(b){return parseInt(b.value,10)});return-1<b.inArray(a.id,d)},D=b(".buse-suggest-user").autocomplete({source:function(a,d){var c,e=a.term;c=b.grep(buse_site_users,function(b){e=e.toLowerCase();return b.user.is_section_editor&&(-1!=b.user.display_name.toLowerCase().indexOf(e)||-1!=b.user.login.toLowerCase().indexOf(e)||-1!=b.user.nicename.toLowerCase().indexOf(e)||-1!=b.user.email.toLowerCase().indexOf(e))});
c=b.grep(c,function(b){return!u(b.user)});c=b.map(c,function(b){return b.autocomplete});d(c)},delay:500,minLength:2,position:"undefined"!==typeof isRtl&&isRtl?{my:"right top",at:"right bottom",offset:"0, -1"}:{offset:"0, -1"},open:function(){b(this).addClass("open")},close:function(){b(this).removeClass("open")}});b("#add_member").bind("click",function(b){b.preventDefault();v()});b("#user_login").keypress(function(b){"13"==b.keyCode&&(b.preventDefault(),v())});var v=function(){var a=b.trim(b("#user_login").val());
if(a){D.autocomplete("search","");var d;d=b.grep(buse_site_users,function(b){var e=a.toLowerCase();return b.user.display_name.toLowerCase()==e||b.user.login.toLowerCase()==e||b.user.nicename.toLowerCase()==e||b.user.email.toLowerCase()==e});d=1<d.length||0==d.length?!1:d[0].user;d?d.is_section_editor?u(d)?(d="<b>"+d.display_name+"</b> "+b("<p/>").html(buse_group_editor_settings.userAlreadyMemberNotice).text(),k(d,"members-message")):(s("members-message"),b("#member_"+d.id).attr("checked","checked").parent(".member").addClass("active").appendTo(q).slideDown("fast"),
t()):(d="<b>"+d.display_name+"</b> "+b("<p/>").html(buse_group_editor_settings.userWrongRoleNotice).text(),k(d,"members-message")):(d="<b>"+a+"</b> "+b("<p/>").html(buse_group_editor_settings.userNotExistsNotice).text(),k(d,"members-message"))}b("#user_login").val("").focus()},t=function(){var a,d;a=q.children(".member").length;d=1==a?buse_group_editor_settings.memberCountSingularLabel:buse_group_editor_settings.memberCountPluralLabel;b(".member-count").html(a);b(".member-count-label").text(d)},y=
function(a){var d=a.find(".perm-editor").first();if(d.hasClass("hierarchical"))if("undefined"===typeof p)alert(buse_group_editor_settings.navDepAlertText),d.html(buse_group_editor_settings.navDepEditorText);else{var c={el:"#"+d.attr("id"),groupID:b("#group_id").val()||-1,postType:d.data("post-type")};b.extend(c,buse_perm_editor_settings);p.tree("buse_perm_editor",c);d.bind("load_node.jstree",function(b,a){-1!=a.rslt.obj&&w(a.rslt.obj)}).bind("perm_updated",function(b,a){var c=a.post;c.hasClass("jstree-closed")&&
d.jstree("open_all",c);d.jstree("deselect_node",c)})}else c=d.data("post-type"),d.delegate("a","click",function(e){e.preventDefault();e.stopPropagation();e=b(this).parent("li").first();e.siblings("li.perm-item-selected").each(function(){b(this).removeClass("perm-item-selected")});e.addClass("perm-item-selected")}),d.bind("perm_updated",function(b,a){a.post.removeClass("perm-item-selected")}),d.bind("posts_loaded.buse",function(){var b=d.data("perm-edits")||{allowed:[],denied:[]},a,c;for(a=0;a<b.allowed.length;a+=
1)c=b.allowed[a],c=d.find("#p"+c),c.length&&l(c,!0,d);for(a=0;a<b.denied.length;a+=1)c=b.denied[a],c=d.find("#p"+c),c.length&&l(c,!1,d)}),r(d,{post_type:c});a.delegate("button.perm-search","click",function(a){a.preventDefault();a=d.data("post-type");a=b("#perm-search-"+a).val();a={post_type:d.data("post-type"),query:{s:a}};r(d,a)});a.find(".pagination-links").delegate("a","click",function(a){a.preventDefault();if(!b(this).hasClass("disabled")){a=b(this).attr("class");var c=parseInt(b(this).parent().find(".current-page").val()),
f=parseInt(b(this).parent().find(".total-pages").text()),h=1;switch(a){case "first-page":h=1;break;case "prev-page":h=c-1;break;case "next-page":h=c+1;break;case "last-page":h=f}x(h,d)}});a.delegate("input.current-page","keypress",function(a){if("13"==a.keyCode){a.preventDefault();a=b(this).val();var c=parseInt(b(this).parent().find(".total-pages").text());1>a?b(this).val(1):a>c&&b(this).val(c);a=b(this).val();x(a,d)}});a.delegate("input.perm-search","keypress",function(a){13==a.keyCode&&(a.preventDefault(),
b(this).siblings("button").first().click())});a.delegate("a.perm-editor-bulk-edit","click",function(c){c.preventDefault();c=b(this);a.hasClass("bulk-edit")?(c.removeClass("bulk-edit-close").attr("title",buse_group_editor_settings.bulkEditOpenTitle).text(buse_group_editor_settings.bulkEditOpenText),a.removeClass("bulk-edit")):(c.addClass("bulk-edit-close").attr("title",buse_group_editor_settings.bulkEditCloseTitle).text(buse_group_editor_settings.bulkEditCloseText),a.addClass("bulk-edit"));d.find(".perm-item-selected").removeClass("perm-item-selected");
a.find('input[type="checkbox"]').attr("checked",!1);b(".bulk-edit-actions select").val("none")});a.delegate(".bulk-edit-select-all","click",function(){d.find("li").children('input[type="checkbox"]').attr("checked",this.checked)});a.delegate(".bulk-edit-actions button","click",function(c){c.preventDefault();c=b(this).siblings("select");var g=c.val(),f=d.find('input[type="checkbox"]:checked');0<f.length&&("allowed"==g||"denied"==g)&&f.each(function(){var a=b(this).parents("li");l(a,"allowed"==g?!0:
!1,d)});a.find(".bulk-edit-select-all").attr("checked",!1);c.val("none");f.attr("checked",!1)});a.delegate("a.perm-tree-expand","click",function(a){a.preventDefault();"undefined"!==typeof b.jstree&&b.jstree._reference(d).open_all()});a.delegate("a.perm-tree-collapse","click",function(a){a.preventDefault();"undefined"!==typeof b.jstree&&b.jstree._reference(d).close_all()});d.delegate(".edit-perms","click",function(a){var c=b(a.currentTarget),f=c.closest("li"),c=c.attr("class"),h;a.stopPropagation();
a.preventDefault();-1<c.indexOf("allowed")?h="allowed":-1<c.indexOf("denied")&&(h="denied");l(f,"allowed"==h?!0:!1,d);d.trigger("perm_updated",[{post:f,action:h}])});b(document).bind("click",function(a){var c=b(".perm-panel.active"),d=b(".perm-editor",c);b.contains(c[0],a.target)||(d.hasClass("hierarchical")&&"undefined"!==typeof d.jstree?d.jstree("deselect_all"):d.find(".perm-item-selected").removeClass("perm-item-selected"))});a.addClass("loaded")};b("#perm-tab-container").delegate("a","click",
function(){var a=b(b(this).attr("href"));a.hasClass("loaded")||y(a)});var x=function(a,d){var c=d.closest(".perm-panel"),e=d.data("post-type"),g={post_type:e,query:{paged:a}},e=b("#perm-search-"+e).val();0<e.length&&(g.query.s=e);c.find(".bulk-edit-select-all").attr("checked",!1);c.find(".bulk-edit-actions select").val("none");r(d,g)},r=function(a,d){var c={action:"buse_render_post_list",group_id:b("#group_id").val()||-1,query:{}};void 0!==typeof d&&b.extend(c,d);a.addClass("loading");c.query.offset?
a.append('<li class="loader">'+buse_group_editor_settings.loadingText+"</li>"):a.html('<ul><li class="loader">'+buse_group_editor_settings.loadingText+"</li></ul>");b.ajax({url:ajaxurl,type:"GET",data:c,cache:!1,success:function(d){c.query.offset?a.append(d.posts):a.html(d.posts);var g=d.page,f=d.found_posts,h=d.max_num_pages,j=a.data("post-type");b("#group_id").val();$pagination=b("#perm-editor-pagination-"+j);$total_items=$pagination.find(".displaying-num");$current_page=$pagination.find(".current-page");
$total_pages=$pagination.find(".total-pages");$first_page=$pagination.find(".first-page");$prev_page=$pagination.find(".prev-page");$next_page=$pagination.find(".next-page");$last_page=$pagination.find(".last-page");1<h?(j=1==parseInt(f)?" item":" items",$total_items.text(f+j),$current_page.val(g),$total_pages.text(h),1==g?($first_page.addClass("disabled"),$prev_page.addClass("disabled")):($first_page.removeClass("disabled"),$prev_page.removeClass("disabled")),g==h?($next_page.addClass("disabled"),
$last_page.addClass("disabled")):($next_page.removeClass("disabled"),$last_page.removeClass("disabled")),$pagination.show()):$pagination.hide();a.trigger("posts_loaded.buse",{posts:d.posts});a.removeClass("loading")},error:function(){}})},l=function(a,d,c){var e=c.data("post-type"),g=c.data("perm-edits")||{allowed:[],denied:[]};z(a,d,g);c.hasClass("hierarchical")&&(a=a.parent("ul").parent("div").attr("id")!=c.attr("id")?a.parents("li:last"):a,w(a));c.data("perm-edits",g);g=b("#"+e+"-stats");c=b(".perm-stats-diff",
g);e=b("#perm-editor-"+e).data("perm-edits");0===c.length&&(c=b('<span class="perm-stats-diff"></span>'),g.append(c));var g=[],f;for(f in e)e[f].length&&(a="allowed"===f?"+":"-",g.push('<span class="'+f+'">'+a+e[f].length+"</span>"));(f=g.join(", "))?c.html(" ("+f+")"):c.html("")},z=function(a,d,c){var e=a.attr("id").substr(1),g=a.find(".edit-perms").first();if(d!=a.data("editable")){var f=g.hasClass("allowed")?"allowed":"denied",h="allowed"==f?"denied":"allowed",j="",j="allowed"==h?buse_group_editor_settings.permAllowLabel:
buse_group_editor_settings.permDenyLabel;g.removeClass(f).addClass(h).text(j)}g=d?"allowed":"denied";f=a.data("editable-original");a.data("editable",d);a.attr("rel",g);f!=d?(f=b.inArray(e,c[g]),-1===f&&c[g].push(e)):(f=b.inArray(e,c.allowed),-1<f&&c.allowed.splice(f,1),f=b.inArray(e,c.denied),-1<f&&c.denied.splice(f,1));a.find("> ul > li").each(function(){z(b(this),d,c)})},w=function(a){$sections=a.find("ul");$sections.each(function(){var a=b(this).parents("li").first();if(a.length){var c=!1;switch(a.attr("rel")){case "allowed":case "allowed-desc-denied":case "allowed-desc-unknown":(c=
b(this).find('li[rel="denied"],li[rel="denied-desc-allowed"],li[rel="denied-desc-unknown"]').length)?a.attr("rel","allowed-desc-denied"):a.attr("rel","allowed");A(a,c);break;case "denied":case "denied-desc-allowed":case "denied-desc-unknown":(c=b(this).find('li[rel="allowed"],li[rel="allowed-desc-denied"],li[rel="allowed-desc-unknown"]').length)?a.attr("rel","denied-desc-allowed"):a.attr("rel","denied"),A(a,c)}}})},A=function(a,d){var c=a.find("> a > .perm-stats"),e=a.data("editable");0===c.length&&
(c=b(' <span class="perm-stats"><ins class="jstree-icon">&nbsp;</ins><span class="label"></span></span>'),a.find("> a > .title-count").after(c));c.removeClass("allowed denied").children(".label").text("");d&&(e?c.addClass("denied").children(".label").text(d+" "+buse_group_editor_settings.permNonEditableLabel):c.addClass("allowed").children(".label").text(d+" "+buse_group_editor_settings.permEditableLabel))},k=function(a,d,c){var e={classes:"error",before_msg:"<p>",after_msg:"</p>"};c&&"object"==typeof c&&
b.extend(e,c);b("#"+(d||"message")).attr("class",e.classes).html(e.before_msg+a+e.after_msg).fadeIn()},s=function(a){b("#"+(a||"message")).fadeOut("fast",function(){b(this).attr("class","").html("")})},B,m;B=b("#edit-group-name").val();var C=function(){var a=[];b("#group-member-list").children("li.member.active").each(function(d,c){a.push(b(c).children("input").first().val())});return a};m=C();window.onbeforeunload=function(){var a=!1,d,c,e;d=b("#edit-group-name").val();B!=d&&(a=!0);d=C();if(m.length!=
d.length)a=!0;else for(e=0;e<m.length;e+=1)-1==b.inArray(m[e],d)&&(a=!0);b(".perm-editor").each(function(){c=b(this).data("perm-edits");if("undefined"!==typeof c&&(c.allowed.length||c.denied.length))a=!0});if(a)return buse_group_editor_settings.dirtyLeaverNotice};b("#group-edit-form").submit(function(){window.onbeforeunload=null;if(1>b.trim(b("#edit-group-name").val()).length)return k(buse_group_editor_settings.nameRequiredNotice),!1;b(".perm-editor").each(function(){var a=b(this).data("perm-edits")||
{allowed:[],denied:[]};b(this).siblings(".buse-edits").val(JSON.stringify(a))})});b("a.submitdelete").click(function(a){a.preventDefault();confirm(buse_group_editor_settings.deleteGroupNotice+"\n\n"+buse_group_editor_settings.confirmActionNotice)&&(window.onbeforeunload=null,window.location=b(this).attr("href"))});if(document.images){var n=new Image,E=new Image;n.src=buse_group_editor_settings.pluginUrl+"/images/group_perms_sprite.png";E.src=buse_group_editor_settings.pluginUrl+"/images/loading.gif"}n=
b("#perm-panel-container").find(".perm-panel.active").first();n.length&&y(n);b("div#message").insertAfter(b("div.wrap h2:first"))});