/*
 * jQuery treeTable Plugin 2.3.0
 * http://ludo.cubicphuse.nl/jquery-plugins/treeTable/
 *
 * Copyright 2010, Ludo van den Boom
 * Dual licensed under the MIT or GPL Version 2 licenses.
 */
(function($) {
  // Helps to make options available to all functions
  // TODO: This gives problems when there are both expandable and non-expandable
  // trees on a page. The options shouldn't be global to all these instances!
  var options;
  var defaultPaddingLeft;
  var globalElement;
  $.fn.treeTable = function(opts) {
    options = $.extend({}, $.fn.treeTable.defaults, opts);
    globalElement=this;
    colorLines(false);
    var tmp =this.each(function() {
      
      
      $(this).addClass("treeTable").find("tbody tr").each(function() {
        // Initialize root nodes only if possible
        if(!options.expandable || $(this)[0].className.search(options.childPrefix) == -1) {
          // To optimize performance of indentation, I retrieve the padding-left
          // value of the first root node. This way I only have to call +css+ 
          // once.
          if (isNaN(defaultPaddingLeft)) {
            defaultPaddingLeft = parseInt($($(this).children("td:first")[options.treeColumn]).css('padding-left'), 10);
          }
          
          initialize($(this));
        } else if(options.initialState == "collapsed") {
          this.style.display = "none"; // Performance! $(this).hide() is slow...
        }
      });
      initializeAjax($(this),true);
    });
    initEvent();
    
    return tmp;
  };
  
  $.fn.treeTable.defaults = {
    childPrefix: "child-of-",
    clickableNodeNames: true,
    expandable: true,
    indent: 19,
    initialState: "collapsed",
    treeColumn: 0
  };
  
  // Recursively hide all node's children in a tree
  $.fn.collapse = function() {
    $(this).addClass("collapsed");
    
    childrenOf($(this)).each(function() {
      if(!$(this).hasClass("collapsed")) {
        $(this).collapse();
      }
      
      this.style.display = "none"; // Performance! $(this).hide() is slow...
    });
    colorLines(true);
    return this;
  };
  
  
    
  var tree= new Array();
  
  function initializeAjax(node,first)
  {
  var folders='';
  var children;
  if(first)
    {
    children= node.find('.parent');
    }
  else
    {
    children=childrenOf(node);
    }
  children.each(function()
      {
      if($(this).attr('ajax')!=undefined)
        {
        folders+=$(this).attr('ajax')+'-';
        $(this).attr('proccessing',true);
        }
      });
  if(folders!='')
    {    
    $.post(json.global.webroot+'/browse/getfolderscontent',{folders: folders} , function(data) {
          arrayElement=jQuery.parseJSON(data);
          $.each(arrayElement, function(index, value) { 
            tree[index]=value;
          });
          children.each(function()
          {          
          if($(this).attr('ajax')!=undefined)
            {
         //   $(this).removeAttr('ajax');
         //   $(this).attr('proccessing',false);
         //   $(this).find('td:first img.tableLoading').hide();
            }
          });
        initEvent();
      });
    }
    getElementsSize();
  }
  
  // Recursively show all node's children in a tree
  $.fn.expand = function() {
    if($(this).attr('ajax')!=undefined&&$(this).attr('proccessing')==undefined)
      {
      initializeAjax(parentOf($(this)),false); 
      return $(this).expand();
      }
    else if ($(this).attr('ajax')!=undefined&&tree[$(this).attr('ajax')]!=undefined)
      {
      createElementsAjax($(this),tree[$(this).attr('ajax')],true);
      $(this).removeAttr('ajax');
      $(this).attr('proccessing',false);
      $(this).find('td:first img.tableLoading').hide();
      initEvent();
      initialize($(this));
      }


    if($(this).attr('proccessing')=='true')
      {
 //     $(this).find('td:first').prepend('<img class="tableLoading" alt="" src="'+json.global.coreWebroot+'/public/images/icons/loading.gif"/>');
      }
    $(this).removeClass("collapsed").addClass("expanded");
        childrenOf($(this)).each(function() {
        initialize($(this));
       
        if($(this).is(".expanded.parent")) {
          $(this).expand();
        }

        // this.style.display = "table-row"; // Unfortunately this is not possible with IE :-(
        $(this).show();
      });
    initializeAjax($(this),false);
    colorLines(true);
    return this;
  };

  // Reveal a node by expanding all ancestors
  $.fn.reveal = function() {
    $(ancestorsOf($(this)).reverse()).each(function() {
      initialize($(this));
      $(this).expand().show();
    });
    
    return this;
  };

  // Add an entire branch to +destination+
  $.fn.appendBranchTo = function(destination) {
    var node = $(this);
    var parent = parentOf(node);
    
    var ancestorNames = $.map(ancestorsOf($(destination)), function(a) {return a.id;});
    
    // Conditions:
    // 1: +node+ should not be inserted in a location in a branch if this would
    //    result in +node+ being an ancestor of itself.
    // 2: +node+ should not have a parent OR the destination should not be the
    //    same as +node+'s current parent (this last condition prevents +node+
    //    from being moved to the same location where it already is).
    // 3: +node+ should not be inserted as a child of +node+ itself.

    if($.inArray(node[0].id, ancestorNames) == -1 && (!parent || (destination.id != parent[0].id)) && destination.id != node[0].id) {
      indent(node, ancestorsOf(node).length * options.indent * -1); // Remove indentation
      
      if(parent) {node.removeClass(options.childPrefix + parent[0].id);}
      
      node.addClass(options.childPrefix + destination.id);
      move(node, destination); // Recursively move nodes to new location
      indent(node, ancestorsOf(node).length * options.indent);
    }
    
    return this;
  };
  
  // Add reverse() function from JS Arrays
  $.fn.reverse = function() {
    return this.pushStack(this.get().reverse(), arguments);
  };
  
  // Toggle an entire branch
  $.fn.toggleBranch = function() {
    if($(this).hasClass("collapsed")) {
      $(this).expand();
    } else {
      $(this).removeClass("expanded").collapse();
    }
    
    return this;
  };
  
  // === Private functions
  
  function ancestorsOf(node) {
    var ancestors = [];
    while(node = parentOf(node)) {
      ancestors[ancestors.length] = node[0];
    }
    return ancestors;
  };
  
  function childrenOf(node) {
     if(node[0]==undefined)
      {
      return null;
      }
    return $("table.treeTable tbody tr." + options.childPrefix + node[0].id);
  };
  
  function getPaddingLeft(node) {
    if(node[0]==undefined)
      {
      return defaultPaddingLeft;
      }
    var paddingLeft = parseInt(node[0].style.paddingLeft, 10);
    return (isNaN(paddingLeft)) ? defaultPaddingLeft : paddingLeft;
  }
  
  function indent(node, value) {
    var cell = $(node.children("td:first")[options.treeColumn]);
    cell[0].style.paddingLeft = getPaddingLeft(cell) + value + "px";
    
    childrenOf(node).each(function() {
      indent($(this), value);
    });
  };

  function initEvent()
  {
          // Make visible that a row is clicked
    globalElement.find("tbody tr").unbind('mousedown');
    globalElement.find("tbody tr").mousedown(function() {
      $("tr.selected").removeClass("selected"); // Deselect currently selected rows
      $(this).addClass("selected");
      if(typeof callbackSelect == 'function') { 
          callbackSelect($(this)); 
          }
    });

    globalElement.find("tbody tr").unbind('dblclick');
    globalElement.find("tbody tr").dblclick(function () { 
         if(typeof callbackDblClick == 'function') { 
          callbackDblClick($(this)); 
          }
        });
       colorLines(true);
       
   globalElement.find(".treeCheckbox").unbind('change');
   globalElement.find(".treeCheckbox").change(function(){
        if(typeof callbackCheckboxes == 'function') { 
          callbackCheckboxes(globalElement); 
          }
   });
  }
  
  function colorLines(checkHidden)
  {
    var grey=false;
    $('.midasTree tr').each(function(index){
      if(index==0)return;
      if(!checkHidden||!$(this).is(':hidden'))
        {
        if(grey)
          {
          $(this).css('background-color','#f9f9f9');
          $(this).hover(function(){$(this).css('background-color','#F3F1EC')}, function(){$(this).css('background-color','#f9f9f9')});
          grey=false; 
          }
        else
          { 
          $(this).css('background-color','white');
          $(this).hover(function(){$(this).css('background-color','#F3F1EC')}, function(){$(this).css('background-color','white')});
          grey=true;
          }
        }
    });
  }

  function trimName(name,padding)
  {
    if(name.length*7+padding>350)
      { 
      toremove=(name.length*7+padding-350)/8;  
      if(toremove<13)
        {
        return 'error';
        }
      name=name.substring(0,10)+'...'+name.substring(name.length+13-toremove);
      return name;
      }
  return name;
  }
  
  function createElementsAjax(node,elements,first)
  {
    if(typeof customElements == 'function')
      { 
        html=customElements(node,elements,first);         
      }
    else
      {
        var i = 1;
        var id=node.attr('id');
        elements['folders'] = jQuery.makeArray(elements['folders']);
        elements['items'] = jQuery.makeArray(elements['items']);
        var padding=parseInt(node.find('td:first').css('padding-left').slice(0,-2));
        var html='';
        $.each(elements['folders'], function(index, value) {
          html+= "<tr id='"+id+"-"+i+"' class='parent child-of-"+id+"' ajax='"+value['folder_id']+"'type='folder'  policy='"+value['policy']+"' element='"+value['folder_id']+"'>";
          html+=     "  <td><span class='folder'>"+trimName(value['name'],padding)+"</span></td>";
          html+=     "  <td>"+'<img class="folderLoading"  element="'+value['folder_id']+'" alt="" src="'+json.global.coreWebroot+'/public/images/icons/loading.gif"/>'+"</td>";
          html+=     "  <td>"+value['creation']+"</td>";
          html+=     "  <td><input type='checkbox' class='treeCheckbox' type='folder' element='"+value['folder_id']+"'/></td>";
          html+=     "</tr>";
          i++;
          });

        $.each(elements['items'], function(index, value) { 
          html+=  "<tr id='"+id+"-"+i+"' class='child-of-"+id+"'  type='item' policy='"+value['policy']+"' element='"+value['item_id']+"'>";
          html+=     "  <td><span class='file'>"+trimName(value['name'],padding)+"</span></td>";
          html+=     "  <td>"+value['size']+"</td>";
          html+=     "  <td>"+value['creation']+"</td>";
          html+=     "  <td><input type='checkbox' class='treeCheckbox' type='item' element='"+value['item_id']+"'/></td>";
          html+=     "</tr>";       
          i++;
          });
      }
    
    node.after(html)
    var cell = $(node.children("td")[options.treeColumn]);
    var padding = getPaddingLeft(cell) + options.indent;
    var arrayCell=childrenOf(node);
    if(arrayCell==null)return;
    arrayCell.each(function() {
      if(first)
        {
        $(this).children("td:first")[options.treeColumn].style.paddingLeft = (padding-12) + "px";
        }
      else
        {
        $(this).children("td:first")[options.treeColumn].style.paddingLeft = (padding-12) + "px"; 
        }
       if(node.hasClass('expanded'))
         {
         initialize($(this));
         $(this).show();
         }
       else
         {
         $(this).hide();  
         }
       });
      
  }
  
  function initialize(node) {
    if(!node.hasClass("initialized")) {
      node.addClass("initialized");
      
      var childNodes = childrenOf(node);
      if(!node.hasClass("parent") && childNodes.length > 0) {
        node.addClass("parent");
      }
      if(node.hasClass("parent")) {
        var cell = $(node.children("td:first")[options.treeColumn]);
        var padding = getPaddingLeft(cell) + options.indent;
        
        childNodes.each(function() {
          $(this).children("td:first")[options.treeColumn].style.paddingLeft =  (padding-12) + "px";
        });

        if(options.expandable) {
          cell.prepend('<span style="margin-left: -' + options.indent + 'px; padding-left: ' + options.indent + 'px" class="expander"></span>');
          $(cell[0].firstChild).click(function() {node.toggleBranch();});
          
          if(options.clickableNodeNames) {
            cell[0].style.cursor = "pointer";
            $(cell).click(function(e) {
              // Don't double-toggle if the click is on the existing expander icon
              if (e.target.className != 'expander') {
                node.toggleBranch();
              }
            });
          }
          
          // Check for a class set explicitly by the user, otherwise set the default class
          if(!(node.hasClass("expanded") || node.hasClass("collapsed"))) {
            node.addClass(options.initialState);
          }

          if(node.hasClass("expanded")) {
            node.expand();
          }
        }
      }
    }
  };
  
  function move(node, destination) {
    node.insertAfter(destination);
    childrenOf(node).reverse().each(function() {move($(this), node[0]);});
  };
  
  function parentOf(node) {
    var classNames = node[0].className.split(' ');
    
    for(key in classNames) {
      if(classNames[key].match(options.childPrefix)) {
        return $("#" + classNames[key].substring(9));
      }
    }
  };
})(jQuery);


  
  var ajaxSizeRequest='';
  function getElementsSize()
  {
    var elements='';
    $('img.folderLoading').each(function()
        {    
        if($(this).attr('process')==undefined)  
          {
          elements+=$(this).attr('element')+'-';
          $(this).attr('process','true');
          }
        }
      );
    if(elements!='')
      {
        ajaxSizeRequest=$.post(json.global.webroot+'/browse/getfolderssize',{folders: elements} , function(data) {
          arrayElement=jQuery.parseJSON(data);
          $.each(arrayElement, function(index, value) { 
              var img=$('img.folderLoading[element='+value.id+']');
              img.after(value.size);
              img.parents('tr').find('td:first span:last').append(' ('+value.count+')');
              img.remove();
          });
        });
      }

  }
  