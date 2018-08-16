<script>
(function() {
    
    /**
     * Toggles element
     * 
     * @param DOM Element element
     */ 
    function toggle(element)
    {
        var style = window.getComputedStyle(element);
        var display = style.getPropertyValue('display');
        if (display == 'none') {
            element.style.display = 'block';
        } else {
            element.style.display = 'none';
        }
    }
    
    /**
     * Toggles filter group on filter click
     * 
     * @param Event event
     */ 
    function filterTypeClickFunction(event)
    {
        var filterGroup = this.parentNode.querySelector(".filter-group");
        toggle(filterGroup);
        var glyphicon = filterGroup.parentNode.querySelector(".glyphicon");

        if (filterGroup.style.display == 'none') {
            glyphicon.classList.remove("rotate90");
            glyphicon.parentNode.classList.remove('border');
        } else {
            glyphicon.classList.add("rotate90");
            glyphicon.parentNode.classList.add('border');
        }
    }

    /**
     * Toggles filter general block on filter sign '+' click
     */ 
    function filterExpandClickFunction()
    {
        var filterMenus = document.querySelectorAll(".grid-view-filter .filter-menu");
        var filterMenu = this.parentNode.parentNode.querySelector(".filter-menu");
        for (var i = 0; i < filterMenus.length; ++i)
        {
            if (filterMenu !== filterMenus[i]) {
                filterMenus[i].style.display = 'none';
                var glyphicon = filterMenus[i].parentNode.querySelector(".glyphicon");
                glyphicon.classList.add("glyphicon-plus");
                glyphicon.classList.remove("glyphicon-minus");
            }
        }
        
        toggle(filterMenu);
        
        if (filterMenu.style.display == 'none') {
            this.classList.add("glyphicon-plus");
            this.classList.remove("glyphicon-minus");
        } else {
            this.classList.remove("glyphicon-plus");
            this.classList.add("glyphicon-minus");
        }
    }
    
    /**
     * Prevents default element behavior
     */
    function preventDefaultBehavior(event)
    {
        event.stopPropagation();
        event.preventDefault();
    }
    
    /**
     * Disables default behavior of DOM Elements
     * 
     * @param DOM Elements formElements
     */ 
    function disableDefaultBehaviorFormElements(formElements)
    {
        for (var i = 0; i < formElements.length; ++i) {
            formElements[i].addEventListener(
                "change",
                function(event)
                {
                    preventDefaultBehavior(event);
                },
                false
            );
        }
    }
    
    /**
     * Filter date select click processing function
     * 
     * @param DOM Element element
     */ 
    function filterDateSelectClickFunction(element)
    {
        var dateSelect = document.createElement("select");
        var inputVar1 = element.closest(".filter-group").querySelector(".input-val1");
        var dateOptions = ['today', 'tomorrow', 'yesterday', 'lastweek', 'lastmonth', 'lastyear', 'certain'];
        var dateOptionsTranslations = [
            "<?= $today ?>",
            "<?= $tomorrow ?>",
            "<?= $yesterday ?>",
            "<?= $lastweek ?>",
            "<?= $lastmonth ?>",
            "<?= $lastyear ?>",
            "<?= $certain ?>",
        ];
        
        for (var i = 0; i < dateOptions.length; ++i)
        {
            var optionToday = document.createElement("option");
            optionToday.value = dateOptions[i];
            optionToday.innerHTML = dateOptionsTranslations[i];
            dateSelect.appendChild(optionToday);
            
        }
        
        dateSelect.onchange = function(event)
        {
            preventDefaultBehavior(event);
        };
        
        dateSelect.classList.add("input-val1");
        dateSelect.classList.add("form-control");
        dateSelect.name = inputVar1.name;
        
        inputVar1.parentNode.replaceChild(dateSelect, inputVar1);
        
    }
    
    /**
     * Erases input value
     * 
     * @param DOM Element element
     */ 
    function filterEraseInputValue(element)
    {
        var inputElement = element.closest(".form-group").querySelector("input.inputValue");
        inputElement.value = '';
    }
    
    var formElements = document.querySelectorAll(".grid-view-filter-form input, .grid-view-filter-form select");
    
    disableDefaultBehaviorFormElements(formElements);
        
    
    var filterTypes = document.querySelectorAll(".grid-view-filter-form .filter-type");
    var filterExpandIcons = document.querySelectorAll(".grid-view-filter-form .filter-prompt .glyphicon");
    var filterValueContainers =  document.querySelectorAll(".grid-view-filter-form .filter-value-container");
    var filterHyperlinks = document.querySelectorAll(".grid-view-filter-form .left-hyperlink a");
    var dateFilterSelects  = document.querySelectorAll(".grid-view-filter-form .filter-menu .filter-group select[name='filterCondition[date]']");
    var cancelButtons = document.querySelectorAll(".grid-view-filter-form .btn-cancel");

    for (var i =0; i < filterTypes.length; ++i)
    {
        filterTypes[i].onclick = filterTypeClickFunction;
    }
    
    for (var i =0; i < filterExpandIcons.length; ++i)
    {
        filterExpandIcons[i].onclick = filterExpandClickFunction;
    }
    
    for (var i = 0; i < dateFilterSelects.length; ++i)
    {
        dateFilterSelects[i].onchange = function()
        {
            filterDateSelectClickFunction(this);
        };
    }
    
    for(var i = 0; i < filterHyperlinks.length; ++i)
    {
        filterHyperlinks[i].onclick = function()
        {
            filterEraseInputValue(this);
        }
    }
    
    for(var i = 0; i < cancelButtons.length; ++i)
    {
        cancelButtons[i].onclick = function()
        {
            this.closest(".grid-view-filter").querySelector(".glyphicon").click();
        }
    }
})();
</script>