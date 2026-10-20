/* Tabs sind reine Darstellung: Felder bleiben im selben Formular und werden
 * weder deaktiviert noch zwischengespeichert. Ohne JavaScript bleibt alles lesbar.
 * Fehler aus nativer und serverseitiger Prüfung öffnen stets den passenden Tab. */
document.querySelectorAll('[data-employee-editor]').forEach(editor => {
    const navigation=editor.querySelector('[data-editor-tabs]');
    const tabs=Array.from(editor.querySelectorAll('[data-editor-tab]'));
    const panels=Array.from(editor.querySelectorAll('[data-editor-panel]'));
    function select(key,focus=false) {
        tabs.forEach(tab=>{
            const active=tab.dataset.editorTab===key;
            tab.setAttribute('aria-selected',String(active));tab.tabIndex=active?0:-1;
            if(active && focus)tab.focus();
        });
        panels.forEach(panel=>{panel.hidden=panel.dataset.editorPanel!==key;});
    }
    navigation.hidden=false;navigation.setAttribute('role','tablist');
    tabs.forEach((tab,index)=>{
        tab.setAttribute('role','tab');
        const matching=panels.filter(panel=>panel.dataset.editorPanel===tab.dataset.editorTab);
        matching.forEach((panel,i)=>{
            if(!panel.id)panel.id=`editor-panel-${tab.dataset.editorTab}-${i}`;
            panel.setAttribute('aria-labelledby',tab.id);panel.setAttribute('role','tabpanel');
        });
        tab.setAttribute('aria-controls',matching.map(panel=>panel.id).join(' '));
        const count=matching.reduce((total,panel)=>total+Math.max(panel.hasAttribute('data-editor-error')?1:0,panel.querySelectorAll('[aria-invalid="true"]').length),0);
        if(count)tab.querySelector('[data-editor-error-count]').textContent=` · ${count} Fehler`;
        tab.addEventListener('click',()=>select(tab.dataset.editorTab));
        tab.addEventListener('keydown',event=>{
            let target;
            if(event.key==='ArrowRight')target=(index+1)%tabs.length;
            if(event.key==='ArrowLeft')target=(index+tabs.length-1)%tabs.length;
            if(event.key==='Home')target=0;
            if(event.key==='End')target=tabs.length-1;
            if(target!==undefined){event.preventDefault();select(tabs[target].dataset.editorTab,true);}
        });
    });
    const firstError=panels.find(panel=>panel.hasAttribute('data-editor-error') || panel.querySelector('[aria-invalid="true"]'));
    select(location.hash==='#delete'?'management':firstError?.dataset.editorPanel || 'person');
    editor.addEventListener('invalid',event=>{
        const panel=event.target.closest('[data-editor-panel]');
        if(panel)select(panel.dataset.editorPanel);
    },true);
    // Verdeckte Pflichtfelder zuerst sichtbar machen, dann die einzelne native
    // Fehlermeldung anzeigen. Gilt auch für Absenden mit Enter; Server prüft erneut.
    const form=editor.querySelector('form.station-form');
    form.noValidate=true;
    form.addEventListener('submit',event=>{
        const invalid=form.querySelector(':invalid');
        if(!invalid)return;
        event.preventDefault();
        const panel=invalid.closest('[data-editor-panel]');
        if(panel)select(panel.dataset.editorPanel);
        let parent=invalid.parentElement;
        while(parent && parent!==form){if(parent.tagName==='DETAILS')parent.open=true;parent=parent.parentElement;}
        invalid.focus();invalid.reportValidity();
    });
});
